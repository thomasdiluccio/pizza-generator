import express from 'express';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const PORT = process.env.PORT || 3000;

/** On Upsun the PHP app arrives as a relationship; locally it is just a port. */
function resolveApiUrl() {
  if (process.env.API_URL) return process.env.API_URL;

  const encoded = process.env.PLATFORM_RELATIONSHIPS;
  if (encoded) {
    const rels = JSON.parse(Buffer.from(encoded, 'base64').toString('utf8'));
    const api = rels.api?.[0];
    if (api) return `${api.scheme || 'http'}://${api.host}:${api.port}`;
  }

  return 'http://127.0.0.1:8899';
}

const API_URL = resolveApiUrl().replace(/\/$/, '');

const app = express();

app.disable('x-powered-by');
app.use(express.json({ limit: '32kb' }));

/** Thin proxy so the browser only ever talks to one origin. */
app.use('/api', async (req, res) => {
  const target = `${API_URL}${req.url === '/' ? '/health' : req.url}`;

  try {
    const upstream = await fetch(target, {
      method: req.method,
      headers: { 'Content-Type': 'application/json' },
      body: ['GET', 'HEAD'].includes(req.method) ? undefined : JSON.stringify(req.body ?? {}),
      signal: AbortSignal.timeout(8000),
    });

    const text = await upstream.text();
    res.status(upstream.status).type('application/json').send(text);
  } catch (error) {
    res.status(502).json({ error: 'oven_unreachable', detail: error.message, target });
  }
});

app.use(express.static(path.join(__dirname, 'public'), { extensions: ['html'] }));

app.get('/health', (_req, res) => res.json({ status: 'ok', api: API_URL }));

app.listen(PORT, () => {
  console.log(`🍕 pizza-generator frontend on http://localhost:${PORT} (api: ${API_URL})`);
});
