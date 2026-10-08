// نقطة تشغيل للاستضافات (Hostinger / Passenger) التي تحمّل الملف بـ require
import('./server/index.js').catch((e) => { console.error(e); process.exit(1); });
