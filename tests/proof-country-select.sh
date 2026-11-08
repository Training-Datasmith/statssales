#!/bin/bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP="$(mktemp)"
TARGET="$REPO_ROOT/statssales.php"
trap 'cp "$BACKUP" "$TARGET"; rm -f "$BACKUP"' EXIT

cp "$TARGET" "$BACKUP"
perl -0pi -e "s/<option value=\"0\"' \. \(\(!Tools::getValue\\('id_country'\\)\)/<option value=\"0\"' . ((!Tools::getValue('id_order_state'))/" "$TARGET"

PHAR="${STATSSALES_PHPUNIT_PHAR:?set STATSSALES_PHPUNIT_PHAR}"
TMP="$(mktemp -d)"
chmod 0700 "$TMP"
cat >"$TMP/phpunit.xml" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="$REPO_ROOT/tests/bootstrap.php" colors="false" backupGlobals="false">
  <testsuite name="module-tests">
    <file>$REPO_ROOT/tests/StatssalesHookTest.php</file>
  </testsuite>
</phpunit>
EOF
if php -d error_reporting=-1 "$PHAR" --configuration "$TMP/phpunit.xml" --filter testFilteredCountryIsTheOnlySelectedOption; then
  echo "Proof failed: test passed with bug reintroduced" >&2
  exit 1
fi
rm -rf "$TMP"
