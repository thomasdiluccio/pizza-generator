#!/usr/bin/env bash
# Runs the PHP API and the Node front end together for local development.
set -euo pipefail

API_PORT="${API_PORT:-8899}"
WEB_PORT="${PORT:-3000}"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cleanup() { kill 0 2>/dev/null || true; }
trap cleanup EXIT INT TERM

php -S "127.0.0.1:${API_PORT}" -t "${root}/api" "${root}/api/index.php" &
API_URL="http://127.0.0.1:${API_PORT}" PORT="${WEB_PORT}" node "${root}/frontend/server.js" &

echo "API  → http://127.0.0.1:${API_PORT}"
echo "App  → http://localhost:${WEB_PORT}"
wait
