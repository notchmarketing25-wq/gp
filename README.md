# NOTCH Tech Store

متجر إلكتروني + لوحة تحكم (على طريقة Shopify) لعلامة **NOTCH Tech**. يعمل على **Node 22.13+** بدون مكتبات (SQLite مدمج)، وعلى Node 18/20 عبر `better-sqlite3` (يُثبَّت تلقائيًا مع `npm install`).

## التشغيل
```bash
npm start     # المتجر: http://localhost:3000   |   اللوحة: http://localhost:3000/admin
```
- أول تشغيل ينشئ بيانات تجريبية وحساب مدير: `admin@notch.tech` / `admin123` (**غيّره من الإعدادات**).
- متغيرات اختيارية: `PORT`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `DATA_DIR`, `UPLOAD_DIR`, `COOKIE_SECURE=1` (خلف HTTPS).
- `npm run reset` يمسح البيانات ويعيد البيانات التجريبية.

## الرفع على Hostinger (Node.js Web App)
1. hPanel → **Websites → Add website → Node.js Apps** → اربطه بمستودع GitHub (أو ارفع الملفات zip).
2. الإعدادات:
   - **Node version:** ‏22.x (أو 20.x)
   - **Entry file:** `app.cjs`  ← مهم (وليس `server/index.js`)
   - **Install command:** `npm install` — **Build:** اتركه فارغًا — **Start:** `npm start`
3. متغيرات البيئة (Environment variables): `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `SESSION_SECRET` (نص عشوائي طويل)، `COOKIE_SECURE=1` (بعد تفعيل SSL).
   يفضّل أيضًا `DATA_DIR` و`UPLOAD_DIR` لمسار خارج مجلد التطبيق (مثل `/home/USER/notch-data`) حتى لا تُمسح البيانات مع كل إعادة نشر.
4. بعد النشر: المتجر على الدومين، واللوحة على `https://الدومين/admin`.
5. لو ظهر خطأ: hPanel → التطبيق → **Logs**. رسالة `SQLite غير متاح` تعني أن `npm install` لم يكتمل أو إصدار Node غير مدعوم.

## لوحة التحكم
الرئيسية (إحصائيات) · الطلبات · المنتجات (صور، أنواع/ألوان، مخزون، SKU، مسودة) · المجموعات · العملاء · أكواد الخصم · الصفحات ·
**تخصيص المتجر** (ألوان، خط، شعار، قوائم، ترتيب وإظهار/إخفاء أقسام الرئيسية مع معاينة) · الإعدادات (الشحن، العملة، اللغة، التواصل، الحساب).

## المتجر
عربي (RTL) / English، بحث، ترتيب، صفحة منتج بأنواع، سلة وإتمام طلب (الدفع عند الاستلام)، كود خصم، sitemap، متجاوب مع الموبايل. الأسعار تُحسب من السيرفر دائمًا.

## الهيكل والتحديث
```
server/  index.js · api.js (API + الطلبات) · storefront.js (صفحات المتجر) · db.js (الإعدادات الافتراضية) · seed.js
public/store/ (تصميم المتجر)    public/admin/ (لوحة التحكم)
```
- شكل المتجر: من "تخصيص المتجر" أو `public/store/store.css`. قسم جديد للرئيسية: `SECTIONS` في `storefront.js` + `SEC_FIELDS` في `admin.js`.
- التحديث: `git pull` وأعد التشغيل — البيانات في `data/` و`uploads/` لا تتأثر (احتفظ بنسخة منهما).
- لاحقًا: بوابة دفع (Paymob/Fawry)، شركات شحن، حسابات عملاء — نقطة الإضافة `createOrder` في `api.js`.
