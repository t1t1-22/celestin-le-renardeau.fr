import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const PORT = Number(process.env.PORT || 8080);
const ROOT = path.resolve('public');
const DATA = path.resolve(process.env.DATA_DIR || './data');
const UPLOADS = path.join(DATA, 'uploads');
const DB = path.join(DATA, 'content.json');
const PASSWORD = process.env.ADMIN_PASSWORD || '';
const production = process.env.NODE_ENV === 'production';
const sessions = new Map();
const loginAttempts = new Map();
fs.mkdirSync(UPLOADS, { recursive: true });

const initial = {
  settings: { heroImage: '/images/jardin.webp' },
  items: [
    { id: 'livre-muguet', type: 'livre', title: 'Le stand de muguet', category: 'La vie à Fleurville', description: 'Lorsque Papa se blesse, Célestin organise un stand de muguet avec l’aide de ses amis.', body: '', cover: '', download: '', externalUrl: '', published: true, createdAt: '', updatedAt: '' },
    { id: 'livre-jardin', type: 'livre', title: 'Le trou dans le jardin', category: 'Petite enquête', description: 'Célestin et Octet découvrent un mystérieux trou au pied de la clôture.', body: '', cover: '/images/jardin.webp', download: '', externalUrl: '', published: true, createdAt: '', updatedAt: '' },
    { id: 'coloriage-muguet', type: 'coloriage', title: 'Le stand de muguet à colorier', category: 'À imprimer', description: 'Un coloriage au format A4.', body: '', cover: '/images/coloriage.webp', download: '/images/coloriage-celestin.pdf', externalUrl: '', published: true, createdAt: '', updatedAt: '' }
  ]
};
if (!fs.existsSync(DB)) fs.writeFileSync(DB, JSON.stringify(initial, null, 2), { mode: 0o600 });
function readDB() { return JSON.parse(fs.readFileSync(DB, 'utf8')); }
function writeDB(data) {
  const temp = DB + '.' + crypto.randomUUID() + '.tmp';
  fs.writeFileSync(temp, JSON.stringify(data, null, 2), { mode: 0o600 });
  fs.renameSync(temp, DB);
}
function headers(extra = {}) {
  return { 'X-Content-Type-Options': 'nosniff', 'Referrer-Policy': 'same-origin', 'X-Frame-Options': 'DENY', 'Content-Security-Policy': "default-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'", ...extra };
}
function json(res, status, data, extra = {}) {
  res.writeHead(status, headers({ 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store', ...extra }));
  res.end(JSON.stringify(data));
}
function error(res, status, message) { json(res, status, { error: message }); }
async function body(req) {
  const parts = []; let size = 0;
  for await (const chunk of req) {
    size += chunk.length;
    if (size > 17 * 1024 * 1024) { const err = new Error('Fichier trop volumineux'); err.status = 413; throw err; }
    parts.push(chunk);
  }
  try { return JSON.parse(Buffer.concat(parts).toString('utf8')); }
  catch { const err = new Error('Données JSON invalides'); err.status = 400; throw err; }
}
function sameOrigin(req) {
  try {
    const origin = new URL(req.headers.origin || '');
    return ['http:', 'https:'].includes(origin.protocol) && origin.host === req.headers.host && req.headers['x-celestin-admin'] === '1';
  } catch { return false; }
}
function session(req) {
  const token = (req.headers.cookie || '').split(';').map(x => x.trim()).find(x => x.startsWith('celestin_session='));
  if (!token) return false;
  const entry = sessions.get(crypto.createHash('sha256').update(token.slice(17)).digest('hex'));
  return !!entry && entry > Date.now();
}
function cookie(value, maxAge) {
  return 'celestin_session=' + value + '; Path=/; HttpOnly; SameSite=Strict; Max-Age=' + maxAge + (production ? '; Secure' : '');
}
function optionalString(value, max = 500) { return typeof value === 'string' ? value.trim().slice(0, max) : ''; }
function validatedItem(raw, prior) {
  if (!raw || !['livre', 'histoire', 'coloriage'].includes(raw.type)) throw Object.assign(new Error('Rubrique invalide'), { status: 400 });
  const title = optionalString(raw.title, 120);
  if (!title) throw Object.assign(new Error('Un titre est nécessaire'), { status: 400 });
  const url = optionalString(raw.externalUrl, 1500);
  if (url && !/^https?:\/\//i.test(url)) throw Object.assign(new Error('Le lien doit commencer par http:// ou https://'), { status: 400 });
  const cover = optionalString(raw.cover, 1500), download = optionalString(raw.download, 1500);
  for (const asset of [cover, download]) {
    if (asset && !/^\/(uploads\/[0-9a-f-]+\.(png|jpg|webp|pdf)|images\/[a-z0-9._-]+\.(png|jpg|webp|pdf))$/i.test(asset)) {
      throw Object.assign(new Error('Fichier non autorisé'), { status: 400 });
    }
  }
  if (raw.published && raw.type === 'coloriage' && !download) throw Object.assign(new Error('Ajoute un fichier à télécharger avant la publication'), { status: 400 });
  return {
    id: prior?.id || crypto.randomUUID(),
    type: raw.type, title, category: optionalString(raw.category, 80),
    description: optionalString(raw.description, 1200), body: optionalString(raw.body, 80000),
    cover, download, externalUrl: url,
    readMinutes: Math.max(1, Math.min(60, Number(raw.readMinutes) || 3)),
    published: raw.published === true, createdAt: prior?.createdAt || new Date().toISOString(),
    updatedAt: new Date().toISOString()
  };
}
function mimeOf(filename) {
  const ext = path.extname(filename).toLowerCase();
  return { '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.pdf': 'application/pdf', '.ico': 'image/x-icon', '.svg': 'image/svg+xml' }[ext] || 'application/octet-stream';
}
function serveFile(res, name, uploads = false) {
  if (!/^\/[a-zA-Z0-9/_\-.]+$/.test(name) || name.includes('..') || name.includes('/.')) return error(res, 404, 'Page introuvable');
  const base = uploads ? UPLOADS : ROOT;
  const relative = uploads ? name.replace(/^\/uploads\//, '') : name.slice(1);
  const file = path.resolve(base, relative);
  if (!file.startsWith(base + path.sep)) return error(res, 404, 'Page introuvable');
  fs.stat(file, (err, stat) => {
    if (err || !stat.isFile()) return error(res, 404, 'Page introuvable');
    const extra = { 'Content-Type': mimeOf(file), 'Cache-Control': uploads ? 'public, max-age=86400' : 'public, max-age=300' };
    if (uploads && file.endsWith('.pdf')) extra['Content-Disposition'] = 'attachment';
    res.writeHead(200, headers(extra));
    fs.createReadStream(file).pipe(res);
  });
}
function upload(item) {
  const mime = optionalString(item.mime, 50);
  const ext = { 'image/png': 'png', 'image/jpeg': 'jpg', 'image/webp': 'webp', 'application/pdf': 'pdf' }[mime];
  if (!ext || typeof item.data !== 'string' || !/^[A-Za-z0-9+/]+={0,2}$/.test(item.data)) throw Object.assign(new Error('Format de fichier non autorisé'), { status: 400 });
  const bytes = Buffer.from(item.data, 'base64');
  if (!bytes.length || bytes.length > 10 * 1024 * 1024) throw Object.assign(new Error('Fichier limité à 10 Mo'), { status: 413 });
  const correct = ext === 'png' && bytes.subarray(0, 8).equals(Buffer.from([137,80,78,71,13,10,26,10]))
    || ext === 'jpg' && bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255
    || ext === 'webp' && bytes.toString('ascii', 0, 4) === 'RIFF' && bytes.toString('ascii', 8, 12) === 'WEBP'
    || ext === 'pdf' && bytes.toString('ascii', 0, 5) === '%PDF-';
  if (!correct) throw Object.assign(new Error('Signature du fichier non valide'), { status: 400 });
  const file = crypto.randomUUID() + '.' + ext;
  fs.writeFileSync(path.join(UPLOADS, file), bytes, { flag: 'wx', mode: 0o644 });
  return '/uploads/' + file;
}
const server = http.createServer(async (req, res) => {
  try {
    const route = new URL(req.url, 'http://localhost').pathname;
    if (req.method === 'GET' && route === '/health') return json(res, 200, { ok: true });
    if (req.method === 'GET' && route === '/api/content') {
      const data = readDB();
      return json(res, 200, { items: data.items.filter(x => x.published), settings: data.settings });
    }
    if (route.startsWith('/api/admin/')) {
      if (req.method !== 'GET' && !sameOrigin(req)) return error(res, 403, 'Requête refusée');
      if (route === '/api/admin/login' && req.method === 'POST') {
        if (PASSWORD.length < 12) return error(res, 503, 'Définir ADMIN_PASSWORD (12 caractères minimum) sur le serveur');
        const ip = req.socket.remoteAddress || 'unknown', now = Date.now();
        const attempts = (loginAttempts.get(ip) || []).filter(t => now - t < 15 * 60_000);
        if (attempts.length >= 7) return error(res, 429, 'Trop de tentatives. Réessaie plus tard.');
        const data = await body(req);
        const supplied = crypto.createHash('sha256').update(String(data.password || '')).digest();
        const expected = crypto.createHash('sha256').update(PASSWORD).digest();
        if (!crypto.timingSafeEqual(supplied, expected)) {
          attempts.push(now); loginAttempts.set(ip, attempts);
          return error(res, 401, 'Mot de passe incorrect');
        }
        loginAttempts.delete(ip);
        const token = crypto.randomBytes(32).toString('hex');
        sessions.set(crypto.createHash('sha256').update(token).digest('hex'), now + 12 * 3600_000);
        return json(res, 200, { ok: true }, { 'Set-Cookie': cookie(token, 12 * 3600) });
      }
      if (!session(req)) return error(res, 401, 'Connexion requise');
      if (route === '/api/admin/me' && req.method === 'GET') return json(res, 200, { ok: true });
      if (route === '/api/admin/logout' && req.method === 'POST') {
        const token = (req.headers.cookie || '').split(';').map(x => x.trim()).find(x => x.startsWith('celestin_session='));
        if (token) sessions.delete(crypto.createHash('sha256').update(token.slice(17)).digest('hex'));
        return json(res, 200, { ok: true }, { 'Set-Cookie': cookie('', 0) });
      }
      if (route === '/api/admin/items' && req.method === 'GET') return json(res, 200, readDB());
      if (route === '/api/admin/upload' && req.method === 'POST') return json(res, 201, { url: upload(await body(req)) });
      if (route === '/api/admin/settings' && req.method === 'PUT') {
        const input = await body(req), hero = optionalString(input.heroImage, 1500);
        if (!/^\/(uploads\/[0-9a-f-]+\.(png|jpg|webp)|images\/[a-z0-9._-]+\.(png|jpg|webp))$/i.test(hero)) return error(res, 400, 'Image invalide');
        const db = readDB(); db.settings.heroImage = hero; writeDB(db);
        return json(res, 200, db.settings);
      }
      if (route === '/api/admin/items' && req.method === 'POST') {
        const db = readDB(), item = validatedItem(await body(req)); db.items.unshift(item); writeDB(db);
        return json(res, 201, item);
      }
      const match = route.match(/^\/api\/admin\/items\/([a-f0-9-]+|livre-muguet|livre-jardin|coloriage-muguet)$/);
      if (match && ['PUT', 'DELETE'].includes(req.method)) {
        const db = readDB(), index = db.items.findIndex(item => item.id === match[1]);
        if (index < 0) return error(res, 404, 'Contenu introuvable');
        if (req.method === 'DELETE') {
          db.items.splice(index, 1); writeDB(db); return json(res, 200, { ok: true });
        }
        const data = await body(req);
        if (data.type !== db.items[index].type) return error(res, 400, 'La rubrique ne peut pas être changée');
        db.items[index] = validatedItem(data, db.items[index]);
        writeDB(db); return json(res, 200, db.items[index]);
      }
      return error(res, 404, 'Page introuvable');
    }
    if (route.startsWith('/api/')) return error(res, 404, 'Page introuvable');
    if (req.method !== 'GET' && req.method !== 'HEAD') return error(res, 405, 'Méthode refusée');
    if (route === '/' || route === '/index.html') return serveFile(res, '/index.html');
    if (route === '/admin') return serveFile(res, '/admin.html');
    if (route.startsWith('/uploads/')) return serveFile(res, route, true);
    return serveFile(res, route);
  } catch (err) {
    console.error(err);
    error(res, err.status || 500, err.status ? err.message : 'Erreur interne');
  }
});
server.listen(PORT, '0.0.0.0', () => console.log('Célestin web on :' + PORT));
