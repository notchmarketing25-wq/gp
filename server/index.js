import http from 'node:http';
import path from 'node:path';
import { ROOT, UPLOAD_DIR } from './db.js';
import { HttpError, Router, makeCtx, serveStatic } from './http.js';
import { ensureAdmin } from './auth.js';
import { seedIfEmpty } from './seed.js';
import { registerApi } from './api.js';
import { registerStore } from './storefront.js';

ensureAdmin();
seedIfEmpty();

const router = new Router();
registerStore(router);
registerApi(router);

const PUBLIC = path.join(ROOT, 'public');
const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, 'http://x');
  const ctx = makeCtx(req, res, url);
  try {
    const p = url.pathname;
    if (req.method === 'GET' || req.method === 'HEAD') {
      if (p.startsWith('/uploads/') && serveStatic(ctx, UPLOAD_DIR, p.slice(9), 'public, max-age=31536000, immutable')) return;
      if (p.startsWith('/store/') && serveStatic(ctx, path.join(PUBLIC, 'store'), p.slice(7))) return;
      if (p === '/admin' || p === '/admin/') return void serveStatic(ctx, path.join(PUBLIC, 'admin'), 'index.html', 'no-cache');
      if (p.startsWith('/admin/') && serveStatic(ctx, path.join(PUBLIC, 'admin'), p.slice(7), 'no-cache')) return;
    }
    const m = router.match(req.method === 'HEAD' ? 'GET' : req.method, p);
    if (!m) return router.notFound(ctx);
    ctx.params = m.params;
    await m.handler(ctx);
  } catch (e) {
    if (e instanceof HttpError) return ctx.json({ error: e.message }, e.status);
    console.error(e);
    ctx.json({ error: 'خطأ في الخادم' }, 500);
  }
});

const port = Number(process.env.PORT) || 3000;
server.listen(port, () => console.log(`NOTCH store → http://localhost:${port}   |   لوحة التحكم → http://localhost:${port}/admin`));
