<?php
/**
 * EmailService — renders branded templates, sends via SMTP (or PHP mail() fallback),
 * and logs every attempt to email_logs for tracking.
 */
class EmailService
{
    /** Send an email using a stored template, substituting {{variables}}. Logs the attempt. */
    public static function send(string $templateKey, string $toEmail, string $toName, array $vars = [], ?string $relatedType = null, ?int $relatedId = null): bool
    {
        if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;
        if (SettingModel::get('email_notifications', '1') !== '1') return false;

        $tpl = Database::fetch("SELECT * FROM email_templates WHERE key_name=? AND is_active=1", [$templateKey]);
        if (!$tpl) return false;

        $vars['store_name'] = SettingModel::get('store_name', APP_NAME);
        $vars['store_url']  = APP_URL;
        $vars['year']       = date('Y');

        $subject = self::substitute($tpl['subject'], $vars);
        $bodyRaw = self::substitute($tpl['html_body'], $vars);
        $html    = self::wrapInLayout($bodyRaw);

        $ok = self::dispatch($toEmail, $toName, $subject, $html);

        Database::insert(
            "INSERT INTO email_logs(template_key,to_email,to_name,subject,status,error_message,related_type,related_id,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
            [$templateKey, $toEmail, $toName, $subject, $ok['ok'] ? 'sent' : 'failed', $ok['error'] ?? null, $relatedType, $relatedId]
        );
        return $ok['ok'];
    }

    private static function substitute(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) { $text = str_replace('{{'.$k.'}}', (string)$v, $text); }
        return $text;
    }

    /** Wrap the template's inner content in the shared branded email shell (header/footer) */
    private static function wrapInLayout(string $inner): string
    {
        $storeName = SettingModel::get('store_name', APP_NAME);
        $logo      = logoUrl('dark');
        $color     = SettingModel::get('primary_color', '#0057FF') ?: '#0057FF';
        $logoHtml  = $logo ? '<img src="'.$logo.'" style="height:32px" alt="'.htmlspecialchars($storeName).'">' : '<span style="color:#fff;font-size:20px;font-weight:800;font-family:Arial,sans-serif">'.htmlspecialchars($storeName).'</span>';

        return '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F4F4F5;font-family:Tahoma,Arial,sans-serif">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F4F5;padding:32px 16px">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06)">
  <tr><td style="background:#0A0A0A;padding:22px 28px">'.$logoHtml.'</td></tr>
  <tr><td style="padding:32px 28px;color:#0A0A0A;font-size:14px;line-height:1.8">'.$inner.'</td></tr>
  <tr><td style="background:#F8F8F8;padding:18px 28px;text-align:center;font-size:11px;color:#9E9E9E;border-top:1px solid #E5E5E5">
    © {{year}} '.htmlspecialchars($storeName).' — جميع الحقوق محفوظة
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';
    }

    /** Actually dispatch the email — uses SMTP if configured, else PHP mail() */
    private static function dispatch(string $toEmail, string $toName, string $subject, string $html): array
    {
        $smtpEnabled = SettingModel::get('smtp_enabled', '0') === '1';
        if ($smtpEnabled) {
            return self::sendViaSmtp($toEmail, $toName, $subject, $html);
        }
        return self::sendViaPhpMail($toEmail, $toName, $subject, $html);
    }

    private static function sendViaPhpMail(string $toEmail, string $toName, string $subject, string $html): array
    {
        $fromEmail = SettingModel::get('smtp_from_email') ?: SettingModel::get('store_email', 'no-reply@' . parse_url(APP_URL, PHP_URL_HOST));
        $fromName  = SettingModel::get('smtp_from_name') ?: SettingModel::get('store_name', APP_NAME);
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?".base64_encode($fromName)."?= <{$fromEmail}>\r\n";
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        if (!function_exists('mail')) {
            return ['ok' => false, 'error' => 'دالة mail() غير متاحة على هذا السيرفر — فعّل SMTP من الإعدادات'];
        }

        error_clear_last();
        $ok = @mail($toEmail, $encodedSubject, $html, $headers);
        if ($ok) return ['ok' => true, 'error' => null];

        $lastErr = error_get_last();
        $reason = $lastErr['message'] ?? 'السيرفر رفض إرسال البريد عبر mail() — الحل الموصى به: فعّل SMTP من صفحة الإعدادات';
        return ['ok' => false, 'error' => $reason];
    }

    /** Minimal SMTP client over a raw socket — no external libraries required */
    private static function sendViaSmtp(string $toEmail, string $toName, string $subject, string $html): array
    {
        $host = SettingModel::get('smtp_host');
        $port = (int)(SettingModel::get('smtp_port', '587') ?: 587);
        $user = SettingModel::get('smtp_username');
        $pass = SettingModel::get('smtp_password');
        $enc  = SettingModel::get('smtp_encryption', 'tls'); // tls | ssl | none
        $fromEmail = SettingModel::get('smtp_from_email') ?: $user;
        $fromName  = SettingModel::get('smtp_from_name') ?: SettingModel::get('store_name', APP_NAME);

        if (!$host || !$user) return ['ok' => false, 'error' => 'إعدادات SMTP غير مكتملة'];

        $transport = $enc === 'ssl' ? "ssl://{$host}" : $host;
        $errno = 0; $errstr = '';
        $fp = @fsockopen($transport, $port, $errno, $errstr, 12);
        if (!$fp) return ['ok' => false, 'error' => "تعذر الاتصال بـ SMTP: $errstr ($errno)"];

        $read = fn() => fgets($fp, 512);
        $cmd  = function(string $c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };

        try {
            $read(); // greeting
            $cmd("EHLO " . parse_url(APP_URL, PHP_URL_HOST));
            if ($enc === 'tls') {
                $cmd("STARTTLS");
                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $cmd("EHLO " . parse_url(APP_URL, PHP_URL_HOST));
            }
            $cmd("AUTH LOGIN");
            $cmd(base64_encode($user));
            $authResp = $cmd(base64_encode($pass));
            if (strpos($authResp, '235') === false) { fclose($fp); return ['ok'=>false,'error'=>'فشل تسجيل الدخول لـ SMTP: '.trim($authResp)]; }

            $cmd("MAIL FROM: <{$fromEmail}>");
            $cmd("RCPT TO: <{$toEmail}>");
            $cmd("DATA");

            $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $msg  = "From: =?UTF-8?B?".base64_encode($fromName)."?= <{$fromEmail}>\r\n";
            $msg .= "To: {$toEmail}\r\n";
            $msg .= "Subject: {$encodedSubject}\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $msg .= str_replace("\n.", "\n..", $html) . "\r\n.";
            $dataResp = $cmd($msg);

            $cmd("QUIT");
            fclose($fp);

            return strpos($dataResp, '250') !== false
                ? ['ok' => true, 'error' => null]
                : ['ok' => false, 'error' => 'SMTP رفض الرسالة: ' . trim($dataResp)];
        } catch (\Throwable $e) {
            @fclose($fp);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Send a raw test email (used by the admin "send test" button) */
    public static function sendTest(string $templateKey, string $toEmail): array
    {
        $tpl = Database::fetch("SELECT * FROM email_templates WHERE key_name=?", [$templateKey]);
        if (!$tpl) return ['ok'=>false,'error'=>'القالب غير موجود'];
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'error'=>'البريد الإلكتروني غير صحيح'];
        if (SettingModel::get('email_notifications', '1') !== '1') return ['ok'=>false,'error'=>'الإرسال معطّل من إعدادات البريد — فعّل "إرسال كل إشعارات البريد" أولاً'];

        $sampleVars = [
            'name' => 'أحمد محمد', 'order_number' => 'NT-10234', 'total' => '1,250.00 ج.م',
            'shop_name' => 'محل تجريبي', 'tier_name' => 'الشريحة الذهبية', 'credit_limit' => '5,000.00 ج.م',
            'status' => 'تم الشحن', 'tracking_number' => 'TRK123456', 'business_type_label' => 'تاجر',
        ];
        $result = self::send($templateKey, $toEmail, 'اختبار', $sampleVars);

        // Pull the real error we just logged, since send() only returns a boolean
        $lastLog = Database::fetch(
            "SELECT error_message FROM email_logs WHERE to_email=? AND template_key=? ORDER BY id DESC LIMIT 1",
            [$toEmail, $templateKey]
        );
        return ['ok' => $result, 'error' => $result ? null : ($lastLog['error_message'] ?? 'سبب غير معروف')];
    }
}
