#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE_DB="${1:-reservas2_1}"
TARGET_DB="${2:-reservas2_test}"
MYSQL_PASSWORD="${MYSQL_ROOT_PASSWORD:-userbt51234}"

cd "$ROOT_DIR"

echo "[test-db] Starting required containers..."
docker compose up -d db php >/dev/null

echo "[test-db] Waiting for MySQL to become healthy..."
docker compose exec -T db sh -lc \
  "until mysqladmin ping -h localhost -uroot -p\"$MYSQL_PASSWORD\" --silent; do sleep 1; done"

echo "[test-db] Rebuilding '$TARGET_DB' from '$SOURCE_DB'..."
docker compose exec -T db sh -lc "
  mysql -uroot -p\"$MYSQL_PASSWORD\" -e \"
    DROP DATABASE IF EXISTS \\\`$TARGET_DB\\\`;
    CREATE DATABASE \\\`$TARGET_DB\\\` CHARACTER SET utf8 COLLATE utf8_general_ci;
  \" &&
  mysqldump -uroot -p\"$MYSQL_PASSWORD\" --single-transaction \"$SOURCE_DB\" |
  mysql -uroot -p\"$MYSQL_PASSWORD\" \"$TARGET_DB\"
"

echo "[test-db] Ready: '$TARGET_DB'"
