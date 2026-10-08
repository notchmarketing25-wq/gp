import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { DATA_DIR, get, run } from './db.js';

const secretFile = path.join(DATA_DIR, '.secret');
const SECRET = process.env.SESSION_SECRET || (fs.existsSync(secretFile)
  ? fs.readFileSync(secretFile, 'utf8')
  : (() => { const s = crypto.randomBytes(32).toString('hex'); fs.writeFileSync(secretFile, s, { mode: 0o600 }); return s; })());

export const hashPassword = (pw, salt = crypto.randomBytes(16).toString('hex')) =>
  ({ salt, hash: crypto.scryptSync(pw, salt, 64).toString('hex') });

export function verifyPassword(pw, user) {
  const a = Buffer.from(hashPassword(pw, user.salt).hash, 'hex');
  return crypto.timingSafeEqual(a, Buffer.from(user.hash, 'hex'));
}

const sign = (v) => crypto.createHmac('sha256', SECRET).update(v).digest('base64url');
export function makeSession(userId, days = 14) {
  const body = `${userId}.${Date.now() + days * 864e5}`;
  return `${body}.${sign(body)}`;
}
export function readSession(token) {
  if (!token) return null;
  const [id, exp, sig] = token.split('.');
  if (!sig || sign(`${id}.${exp}`) !== sig || Number(exp) < Date.now()) return null;
  return get('SELECT id,email,name FROM users WHERE id=?', Number(id)) || null;
}

export function ensureAdmin() {
  if (get('SELECT id FROM users LIMIT 1')) return false;
  const email = process.env.ADMIN_EMAIL || 'admin@notch.tech';
  const pw = process.env.ADMIN_PASSWORD || 'admin123';
  const { salt, hash } = hashPassword(pw);
  run('INSERT INTO users(email,name,salt,hash) VALUES(?,?,?,?)', email, 'Admin', salt, hash);
  console.log(`\n  أول تشغيل → تم إنشاء حساب المدير: ${email} / ${pw}\n  غيّر كلمة المرور فورًا من لوحة التحكم.\n`);
  return true;
}
