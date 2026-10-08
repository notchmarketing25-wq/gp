import fs from 'node:fs';
import path from 'node:path';

const MIME = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json', '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
  '.webp': 'image/webp', '.gif': 'image/gif', '.ico': 'image/x-icon', '.woff2': 'font/woff2', '.txt': 'text/plain; charset=utf-8',
};

export class HttpError extends Error {
  constructor(status, message) { super(message); this.status = status; }
}

export class Router {
  routes = [];
  add(method, pattern, handler) {
    const keys = [];
    const re = new RegExp('^' + pattern.replace(/:(\w+)/g, (_, k) => (keys.push(k), '([^/]+)')) + '/?$');
    this.routes.push({ method, re, keys, handler });
  }
  get(p, h) { this.add('GET', p, h); }
  post(p, h) { this.add('POST', p, h); }
  put(p, h) { this.add('PUT', p, h); }
  del(p, h) { this.add('DELETE', p, h); }
  match(method, pathname) {
    for (const r of this.routes) {
      if (r.method !== method) continue;
      const m = r.re.exec(pathname);
      if (m) return { handler: r.handler, params: Object.fromEntries(r.keys.map((k, i) => [k, decodeURIComponent(m[i + 1])])) };
    }
    return null;
  }
}

export function parseCookies(header = '') {
  const out = {};
  for (const part of header.split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

export async function readJson(req, limit = 8 * 1024 * 1024) {
  const chunks = []; let size = 0;
  for await (const c of req) {
    size += c.length;
    if (size > limit) throw new HttpError(413, 'الطلب كبير جدًا');
    chunks.push(c);
  }
  if (!size) return {};
  try { return JSON.parse(Buffer.concat(chunks).toString('utf8')); } catch { throw new HttpError(400, 'JSON غير صالح'); }
}

export function makeCtx(req, res, url) {
  const ctx = {
    req, res, url, query: Object.fromEntries(url.searchParams), params: {}, cookies: parseCookies(req.headers.cookie),
    setCookies: [],
    cookie(name, value, { maxAge, httpOnly = true } = {}) {
      let c = `${name}=${encodeURIComponent(value)}; Path=/; SameSite=Lax${httpOnly ? '; HttpOnly' : ''}`;
      if (maxAge !== undefined) c += `; Max-Age=${maxAge}`;
      if (process.env.COOKIE_SECURE === '1') c += '; Secure';
      ctx.setCookies.push(c);
    },
    send(status, type, body, headers = {}) {
      const h = { 'Content-Type': type, ...headers };
      if (ctx.setCookies.length) h['Set-Cookie'] = ctx.setCookies;
      res.writeHead(status, h); res.end(body);
    },
    json(data, status = 200) { ctx.send(status, 'application/json; charset=utf-8', JSON.stringify(data), { 'Cache-Control': 'no-store' }); },
    html(body, status = 200) { ctx.send(status, 'text/html; charset=utf-8', body); },
    redirect(to, status = 302) { ctx.send(status, 'text/plain', '', { Location: to }); },
    body() { return readJson(req); },
  };
  return ctx;
}

export function serveStatic(ctx, dir, rel, cache = 'public, max-age=300') {
  const file = path.join(dir, path.normalize(rel).replace(/^(\.\.[/\\])+/, ''));
  if (!file.startsWith(dir) || !fs.existsSync(file) || !fs.statSync(file).isFile()) return false;
  const type = MIME[path.extname(file).toLowerCase()] || 'application/octet-stream';
  ctx.send(200, type, fs.readFileSync(file), { 'Cache-Control': cache, 'X-Content-Type-Options': 'nosniff' });
  return true;
}
