import fs from 'node:fs';
import { DATA_DIR } from './db.js';
fs.rmSync(DATA_DIR, { recursive: true, force: true });
console.log('تم حذف قاعدة البيانات. شغّل npm start لإعادة إنشائها بالبيانات التجريبية.');
