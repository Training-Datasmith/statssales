#!/bin/bash
set -euo pipefail

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP_IMAGE='statssales-php:5.6.40'
PHP_BASE='php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143'
MARIADB_IMAGE='mariadb@sha256:8f96ddc0393e48416ef285acfec62a7c40be760968a72a6144395cccec8a1c24'
PHAR_SHA='ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e'
GPG_FP='D8406D0D82947747293778314AA394086372C20A'

if [[ "$USE_SUDO_DOCKER" == "1" ]]; then
  DOCKER=(sudo docker)
else
  DOCKER=(docker)
fi

cleanup() {
  "${DOCKER[@]}" rm -f statssales-mariadb >/dev/null 2>&1 || true
  if [[ -n "${TMP_TOOL_DIR:-}" && -d "$TMP_TOOL_DIR" ]]; then
    rm -rf "$TMP_TOOL_DIR"
  fi
}
trap cleanup EXIT

pick_port() {
  python3 - <<'PY'
import socket
s = socket.socket()
s.bind(('127.0.0.1', 0))
print(s.getsockname()[1])
s.close()
PY
}

TMP_TOOL_DIR="$(mktemp -d)"
chmod 0700 "$TMP_TOOL_DIR"

GNUPGHOME="$(mktemp -d)"
chmod 0700 "$GNUPGHOME"
export GNUPGHOME

curl -fsSL -o "$TMP_TOOL_DIR/phpunit-4.8.36.phar" https://phar.phpunit.de/phpunit-4.8.36.phar
curl -fsSL -o "$TMP_TOOL_DIR/phpunit-4.8.36.phar.asc" https://phar.phpunit.de/phpunit-4.8.36.phar.asc
echo "$PHAR_SHA  $TMP_TOOL_DIR/phpunit-4.8.36.phar" | sha256sum -c -

if ! gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys "$GPG_FP" 2>/dev/null; then
  curl -fsSL https://sebastian-bergmann.de/gpg.asc | gpg --batch --import
fi
gpg --with-colons --fingerprint "$GPG_FP" | grep -q "$GPG_FP"
GPG_STATUS="$(gpg --batch --status-fd 1 --verify "$TMP_TOOL_DIR/phpunit-4.8.36.phar.asc" "$TMP_TOOL_DIR/phpunit-4.8.36.phar" 2>&1)"
echo "$GPG_STATUS" | grep -q "VALIDSIG $GPG_FP"

"${DOCKER[@]}" build -t "$PHP_IMAGE" -f "$REPO_ROOT/tests/docker/php56.Dockerfile" "$REPO_ROOT/tests/docker"
"${DOCKER[@]}" run --rm "$PHP_IMAGE" php -v | grep -F 'PHP 5.6.40'
"${DOCKER[@]}" run --rm "$PHP_IMAGE" php -m | grep -Fx mysqli
"${DOCKER[@]}" run --rm "$PHP_IMAGE" php -m | grep -Fx dom

PORT=""
for _try in 1 2 3 4 5; do
  PORT="$(pick_port)"
  "${DOCKER[@]}" rm -f statssales-mariadb >/dev/null 2>&1 || true
  if "${DOCKER[@]}" run -d --rm --network host --name statssales-mariadb \
    -e MYSQL_DATABASE=statssales_test \
    -e MYSQL_USER=statssales \
    -e MYSQL_PASSWORD=statssales \
    -e MYSQL_ROOT_PASSWORD=rootpass \
    "$MARIADB_IMAGE" \
    --bind-address=127.0.0.1 --port="$PORT" >/dev/null; then
    break
  fi
  PORT=""
done
if [[ -z "$PORT" ]]; then
  echo "Failed to start MariaDB" >&2
  exit 1
fi

ready=0
for _ in $(seq 1 40); do
  if "${DOCKER[@]}" run --rm --network host \
    -e STATSSALES_DB_HOST=127.0.0.1 \
    -e STATSSALES_DB_PORT="$PORT" \
    -e STATSSALES_DB_USER=statssales \
    -e STATSSALES_DB_PASSWORD=statssales \
    -e STATSSALES_DB_NAME=statssales_test \
    "$PHP_IMAGE" php -d error_reporting=0 -r '
$h=getenv("STATSSALES_DB_HOST"); $p=(int)getenv("STATSSALES_DB_PORT");
$u=getenv("STATSSALES_DB_USER"); $pw=getenv("STATSSALES_DB_PASSWORD"); $db=getenv("STATSSALES_DB_NAME");
$m=mysqli_connect($h,$u,$pw,$db,$p); if($m){ mysqli_close($m); exit(0);} exit(1);
' 2>/dev/null; then
    ready=1
    break
  fi
  sleep 0.5
done
if [[ "$ready" -ne 1 ]]; then
  echo "MariaDB not ready" >&2
  exit 1
fi

export STATSSALES_PHPUNIT_PHAR="$TMP_TOOL_DIR/phpunit-4.8.36.phar"

for SEED in 20261108 20261109; do
  "${DOCKER[@]}" run --rm --network host \
    -v "$REPO_ROOT":/src:ro \
    -w /src \
    -e STATSSALES_DB_HOST=127.0.0.1 \
    -e STATSSALES_DB_PORT="$PORT" \
    -e STATSSALES_DB_NAME=statssales_test \
    -e STATSSALES_DB_USER=statssales \
    -e STATSSALES_DB_PASSWORD=statssales \
    -e STATSSALES_PHPUNIT_PHAR=/phar/phpunit.phar \
    -v "$TMP_TOOL_DIR/phpunit-4.8.36.phar":/phar/phpunit.phar:ro \
    "$PHP_IMAGE" \
    php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d date.timezone=UTC /src/tests/run-suite.php "$SEED"
done

echo "Suite passed both seeds."
