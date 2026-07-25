#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT_DIR"

if [ "${SKIP_TEST_DB_PREPARE:-0}" != "1" ]; then
  "$ROOT_DIR/scripts/prepare_reservas2_test_db.sh"
else
  echo "[test-db] Skipping preparation because SKIP_TEST_DB_PREPARE=1"
fi

if [ "$#" -lt 1 ]; then
  echo "Usage: $0 <suite> [test-file ...]" >&2
  exit 1
fi

SUITE="$1"
shift

if [ "$#" -eq 0 ]; then
  echo "[tests] Running Codeception suite: $SUITE"
  docker compose exec -T php php vendor/bin/codecept run "$SUITE"
  exit 0
fi

for test_file in "$@"; do
  echo "[tests] Running Codeception: $SUITE $test_file"
  docker compose exec -T php php vendor/bin/codecept run "$SUITE" "$test_file"
done
