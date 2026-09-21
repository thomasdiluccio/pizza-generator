# 🍕 Pizza Generator

A small, deliberately fun demo app: build a pizza one layer at a time — base, sauce,
cheese, toppings, finish — watch it stack up on screen, then send it to the oven and
get a price, a bake time, a made-up name and a verdict you did not ask for.

Two apps, one repo:

| App | Stack | Job |
| --- | --- | --- |
| `api/` | PHP 8.4, zero framework | Ingredient catalog, validation, pricing, scoring, storage |
| `frontend/` | Node 22 + Express 5 | Serves the UI, proxies `/api` so the browser sees one origin |

The front end is plain ES modules and CSS — no build step, no bundler, nothing to wait for.

## Run it locally

```bash
cd frontend && npm install && cd ..
./dev.sh
```

- App: http://localhost:3000
- API: http://127.0.0.1:8899

`dev.sh` starts PHP's built-in server and the Node server together and stops both on Ctrl-C.
Pizzas are stored in SQLite (`$TMPDIR/pizza-generator.sqlite`) locally and PostgreSQL on Upsun.
If neither is reachable, the API keeps the last 25 pizzas in memory rather than failing — it is a demo.

## API

| Method | Path | What it does |
| --- | --- | --- |
| `GET` | `/health` | Status, PHP version, which storage backend is live |
| `GET` | `/ingredients` | The full layer catalog with prices and min/max rules |
| `GET` | `/surprise` | A random but legal selection, for the indecisive |
| `POST` | `/pizzas` | Bakes a pizza: validates, prices, names, scores, saves |
| `GET` | `/pizzas` | The 12 most recently baked pizzas |

```bash
curl -s localhost:3000/api/pizzas -X POST -H 'Content-Type: application/json' -d '{
  "chef": "Thomas",
  "selection": {
    "base": "napoletana",
    "sauce": "san-marzano",
    "cheese": ["buffalo"],
    "toppings": ["basil"],
    "finish": []
  }
}'
```

Bad selections come back as `422` with a list of what is wrong ("At most 5 for Toppings, you picked 6.").

## Deploy to Upsun

`.upsun/config.yaml` defines both apps, a PostgreSQL 17 service and the routes.

```bash
upsun project:create --title "Pizza Generator"   # or: upsun project:set-remote <id>
git push upsun main
```

Routes:

- `https://{default}/` → the front end
- `https://api.{default}/` → the JSON API, handy for showing the layers in a demo

The front end finds the API through the `api` relationship (`PLATFORM_RELATIONSHIPS`), so nothing
is hardcoded. Set `API_URL` to override it anywhere else.

## Where things live

```
api/
  index.php        front controller + routes
  src/Catalog.php  the menu: layers, options, prices, jokes
  src/Oven.php     validation, pricing, naming, scoring
  src/Db.php       PostgreSQL / SQLite / in-memory storage
frontend/
  server.js        Express: static files + /api proxy
  public/          index.html, css/styles.css, js/app.js
.upsun/config.yaml two apps, one service, two routes
dev.sh             run both locally
```

## Tuning the demo

Everything interesting is data. Add a topping in `api/src/Catalog.php` (id, name, emoji, price,
color, note) and it shows up in the UI, in the pricing and on the pizza with no front-end change.
Scoring rules — the pineapple-and-anchovy penalty, the hot-honey bonus — live in `Oven::verdict()`.
