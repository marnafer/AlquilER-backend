#!/usr/bin/env bash

set -Eeuo pipefail

REPOSITORY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DEPLOY_SCRIPT="$REPOSITORY_ROOT/scripts/deploy/alwaysdata-release.sh"
TEST_ROOT="$(mktemp -d)"
BARE_REPOSITORY="$TEST_ROOT/repository.git"
SOURCE="$TEST_ROOT/source"
BASELINE="$TEST_ROOT/baseline"
DEPLOY_ROOT="$TEST_ROOT/deploy"
MOCK_BIN="$TEST_ROOT/bin"

cleanup() {
    rm -rf -- "$TEST_ROOT"
}
trap cleanup EXIT

mkdir -p "$SOURCE/src" "$SOURCE/public" "$SOURCE/storage/logs/development" \
    "$SOURCE/storage/logs/production" "$BASELINE/public" "$DEPLOY_ROOT/releases" \
    "$DEPLOY_ROOT/shared/public/uploads" "$DEPLOY_ROOT/shared/storage/logs" \
    "$MOCK_BIN"

printf '%s\n' '{"name":"test/release","require":{},"scripts":{}}' \
    > "$SOURCE/composer.json"
printf '%s\n' '{"packages":[],"packages-dev":[],"aliases":[],"minimum-stability":"stable","stability-flags":{},"prefer-stable":false,"prefer-lowest":false,"platform":{},"platform-dev":{},"plugin-api-version":"2.6.0"}' \
    > "$SOURCE/composer.lock"
printf '%s\n' '<?php echo "source";' > "$SOURCE/src/example.php"
printf '%s\n' '<?php echo "health fixture";' > "$SOURCE/public/index.php"
touch "$SOURCE/storage/logs/development/.gitkeep"
touch "$SOURCE/storage/logs/production/.gitkeep"
printf '%s\n' '<?php echo "baseline";' > "$BASELINE/public/index.php"

ln -s "$BASELINE" "$TEST_ROOT/symlink-check"
if [[ ! -L "$TEST_ROOT/symlink-check" ]]; then
    printf 'Skipping release test: this platform does not support symbolic links.\n'
    exit 0
fi

printf '%s\n' 'DB_DATABASE=test' > "$DEPLOY_ROOT/shared/.env"
printf '%s\n' '{}' > "$DEPLOY_ROOT/shared/debug.log"
printf '' > "$DEPLOY_ROOT/shared/storage/errores.log"
printf '' > "$DEPLOY_ROOT/shared/storage/emails.log"

cat > "$MOCK_BIN/composer" <<'MOCK'
#!/usr/bin/env bash
exit 0
MOCK

cat > "$MOCK_BIN/curl" <<'MOCK'
#!/usr/bin/env bash
current="$(readlink -f "$ALQUILER_DEPLOY_ROOT/current")"
if [[ -n "${MOCK_FAIL_RELEASE_SHA:-}" \
    && "$current" == "$ALQUILER_DEPLOY_ROOT/releases/$MOCK_FAIL_RELEASE_SHA" ]]
then
    exit 22
fi
printf '%s\n' '{"success":true,"data":{"status":"ok"}}'
MOCK

cat > "$MOCK_BIN/sleep" <<'MOCK'
#!/usr/bin/env bash
exit 0
MOCK
chmod 700 "$MOCK_BIN/composer" "$MOCK_BIN/curl" "$MOCK_BIN/sleep"

git init --bare -q "$BARE_REPOSITORY"
git init -q -b master "$SOURCE"
git -C "$SOURCE" config user.name "Deployment Test"
git -C "$SOURCE" config user.email "deployment-test@example.invalid"
git -C "$SOURCE" add .
git -C "$SOURCE" commit -q -m "Initial release"
FIRST_SHA="$(git -C "$SOURCE" rev-parse HEAD)"
git --git-dir="$BARE_REPOSITORY" fetch -q "$SOURCE" master
git --git-dir="$BARE_REPOSITORY" update-ref refs/heads/master "$FIRST_SHA"
ln -s "$BASELINE" "$DEPLOY_ROOT/current"

export ALQUILER_DEPLOY_ROOT="$DEPLOY_ROOT"
export ALQUILER_BARE_REPOSITORY="$BARE_REPOSITORY"
export PATH="$MOCK_BIN:$PATH"

bash "$DEPLOY_SCRIPT" deploy "$FIRST_SHA" "https://example.invalid/api/health"
[[ "$(readlink -f "$DEPLOY_ROOT/current")" == "$DEPLOY_ROOT/releases/$FIRST_SHA" ]]
[[ -L "$DEPLOY_ROOT/releases/$FIRST_SHA/.env" ]]
[[ -L "$DEPLOY_ROOT/releases/$FIRST_SHA/public/uploads" ]]
[[ -L "$DEPLOY_ROOT/releases/$FIRST_SHA/storage/logs" ]]
[[ ! -e "$DEPLOY_ROOT/.deploy-lock" ]]

printf '%s\n' '<?php echo "next";' > "$SOURCE/src/example.php"
git -C "$SOURCE" add src/example.php
git -C "$SOURCE" commit -q -m "Next release"
SECOND_SHA="$(git -C "$SOURCE" rev-parse HEAD)"
git --git-dir="$BARE_REPOSITORY" fetch -q "$SOURCE" master
git --git-dir="$BARE_REPOSITORY" update-ref refs/heads/master "$SECOND_SHA"
export MOCK_FAIL_RELEASE_SHA="$SECOND_SHA"

if bash "$DEPLOY_SCRIPT" deploy "$SECOND_SHA" "https://example.invalid/api/health"; then
    printf 'Expected the simulated health check to fail.\n' >&2
    exit 1
fi

[[ "$(readlink -f "$DEPLOY_ROOT/current")" == "$DEPLOY_ROOT/releases/$FIRST_SHA" ]]
[[ ! -e "$DEPLOY_ROOT/.deploy-lock" ]]
printf 'Release preparation and health-check rollback test passed.\n'
