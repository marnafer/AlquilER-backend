#!/usr/bin/env bash

set -Eeuo pipefail
umask 007

MODE="${1:-}"
COMMIT="${2:-}"
HEALTH_URL="${3:-}"

DEPLOY_ROOT="${ALQUILER_DEPLOY_ROOT:-$HOME/www/alquiler-backend-cd}"
BARE_REPOSITORY="${ALQUILER_BARE_REPOSITORY:-$HOME/alquiler-backend.git}"
RELEASES="$DEPLOY_ROOT/releases"
SHARED="$DEPLOY_ROOT/shared"
CURRENT="$DEPLOY_ROOT/current"
LOCK_DIRECTORY="$DEPLOY_ROOT/.deploy-lock"
BUILD_DIRECTORY=""
LOCK_ACQUIRED=0

fail() {
    printf 'Deploy error: %s\n' "$1" >&2
    exit 1
}

cleanup() {
    if [[ "$LOCK_ACQUIRED" -eq 1 ]]; then
        rmdir -- "$LOCK_DIRECTORY" 2>/dev/null || true
    fi
}

require_file() {
    [[ -f "$1" ]] || fail "Falta el archivo persistente requerido: $1"
}

require_directory() {
    [[ -d "$1" ]] || fail "Falta el directorio persistente requerido: $1"
}

health_check() {
    local attempt
    local response

    for attempt in 1 2 3 4 5; do
        response=""

        if response="$(curl --fail --silent --show-error \
            --connect-timeout 5 --max-time 15 "$HEALTH_URL" 2>/dev/null)"
        then
            if printf '%s' "$response" | php -r '
                $response = json_decode(stream_get_contents(STDIN), true);
                exit(
                    is_array($response)
                    && ($response["success"] ?? false) === true
                    && ($response["data"]["status"] ?? null) === "ok"
                    ? 0
                    : 1
                );
            '; then
                return 0
            fi
        fi

        sleep 2
    done

    return 1
}

switch_current() {
    local target="$1"
    local temporary_link="$DEPLOY_ROOT/.current.$$"

    [[ ! -e "$temporary_link" && ! -L "$temporary_link" ]] \
        || fail "Ya existe un enlace temporal de activación: $temporary_link"

    ln -s -- "$target" "$temporary_link"

    if ! mv -Tf -- "$temporary_link" "$CURRENT"; then
        rm -f -- "$temporary_link"
        fail "No se pudo cambiar el enlace current."
    fi
}

if [[ "$MODE" != "deploy" && "$MODE" != "rollback" ]]; then
    fail "Uso: bash alwaysdata-release.sh {deploy|rollback} <commit-sha> <https-health-url>"
fi

[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || fail "El SHA debe tener 40 caracteres hexadecimales."
[[ "$HEALTH_URL" =~ ^https://[A-Za-z0-9.-]+(:[0-9]+)?(/[^[:space:]]*)?$ ]] \
    || fail "La URL de health check debe ser HTTPS y no contener espacios."

for command_name in curl php readlink sleep; do
    command -v "$command_name" >/dev/null 2>&1 \
        || fail "No está disponible el comando requerido: $command_name"
done

if [[ "$MODE" == "deploy" ]]; then
    for command_name in composer find git tar xargs; do
        command -v "$command_name" >/dev/null 2>&1 \
            || fail "No está disponible el comando requerido: $command_name"
    done
fi

[[ -d "$BARE_REPOSITORY" ]] || fail "No existe el repositorio bare de AlwaysData."
[[ -d "$DEPLOY_ROOT" ]] || fail "No existe el directorio de despliegue preparado."
[[ -d "$RELEASES" ]] || fail "No existe el directorio de releases preparado."

require_file "$SHARED/.env"
require_file "$SHARED/debug.log"
require_directory "$SHARED/public/uploads"
require_directory "$SHARED/storage/logs"
require_file "$SHARED/storage/errores.log"
require_file "$SHARED/storage/emails.log"

if ! mkdir -- "$LOCK_DIRECTORY" 2>/dev/null; then
    fail "Ya hay un deploy en curso o quedó un lock. Comprueba procesos antes de retirarlo: $LOCK_DIRECTORY"
fi
LOCK_ACQUIRED=1
trap cleanup EXIT

[[ -L "$CURRENT" ]] || fail "current debe ser un enlace simbólico inicializado antes del deploy."
PREVIOUS_RELEASE="$(readlink -f -- "$CURRENT")" \
    || fail "No se pudo resolver el destino actual."
[[ -d "$PREVIOUS_RELEASE/public" && -f "$PREVIOUS_RELEASE/public/index.php" ]] \
    || fail "El destino actual no parece una instalación funcional del backend."

RELEASE_DIRECTORY="$RELEASES/$COMMIT"

if [[ "$MODE" == "deploy" ]]; then
    REMOTE_MASTER="$(git --git-dir="$BARE_REPOSITORY" rev-parse --verify 'refs/heads/master^{commit}')" \
        || fail "No se pudo resolver refs/heads/master en el repositorio bare."
    [[ "$REMOTE_MASTER" == "$COMMIT" ]] \
        || fail "El SHA solicitado no coincide con master en AlwaysData."

    if [[ -d "$RELEASE_DIRECTORY" ]]; then
        [[ -f "$RELEASE_DIRECTORY/.deploy-commit" ]] \
            || fail "Ya existe una release incompleta para este SHA."
        [[ "$(<"$RELEASE_DIRECTORY/.deploy-commit")" == "$COMMIT" ]] \
            || fail "El marcador de la release existente no coincide con el SHA."
    else
        umask 007
        BUILD_DIRECTORY="$(mktemp -d "$RELEASES/.build-$COMMIT.XXXXXX")" \
            || fail "No se pudo crear el directorio temporal de release."

        git --git-dir="$BARE_REPOSITORY" archive --format=tar "$COMMIT" \
            | tar -xf - -C "$BUILD_DIRECTORY"

        [[ -f "$BUILD_DIRECTORY/composer.json" && -f "$BUILD_DIRECTORY/composer.lock" ]] \
            || fail "El commit no contiene composer.json y composer.lock."

        mkdir -p -- "$BUILD_DIRECTORY/public" "$BUILD_DIRECTORY/storage"

        for log_directory in \
            "$BUILD_DIRECTORY/storage/logs/development" \
            "$BUILD_DIRECTORY/storage/logs/production"
        do
            if [[ -d "$log_directory" ]]; then
                unexpected_file="$(
                    find "$log_directory" -mindepth 1 -maxdepth 1 \
                        ! -name '.gitkeep' -print -quit
                )"
                [[ -z "$unexpected_file" ]] \
                    || fail "La release contiene un archivo versionado inesperado en storage/logs."
                rm -f -- "$log_directory/.gitkeep"
                rmdir -- "$log_directory"
            fi
        done

        if [[ -d "$BUILD_DIRECTORY/storage/logs" ]]; then
            rmdir -- "$BUILD_DIRECTORY/storage/logs"
        fi

        ln -s -- "$SHARED/.env" "$BUILD_DIRECTORY/.env"
        ln -s -- "$SHARED/debug.log" "$BUILD_DIRECTORY/debug.log"
        ln -s -- "$SHARED/public/uploads" "$BUILD_DIRECTORY/public/uploads"
        ln -s -- "$SHARED/storage/logs" "$BUILD_DIRECTORY/storage/logs"
        ln -s -- "$SHARED/storage/errores.log" "$BUILD_DIRECTORY/storage/errores.log"
        ln -s -- "$SHARED/storage/emails.log" "$BUILD_DIRECTORY/storage/emails.log"
        mkdir -p -- "$BUILD_DIRECTORY/storage/cache"

        composer install \
            --working-dir="$BUILD_DIRECTORY" \
            --no-dev \
            --prefer-dist \
            --no-interaction \
            --no-progress \
            --optimize-autoloader
        composer check-platform-reqs \
            --working-dir="$BUILD_DIRECTORY" \
            --no-dev

        find "$BUILD_DIRECTORY/src" "$BUILD_DIRECTORY/public" \
            -type f -name '*.php' -print0 \
            | xargs -0 -r -n1 php -l > /dev/null

        printf '%s\n' "$COMMIT" > "$BUILD_DIRECTORY/.deploy-commit"
        mv -- "$BUILD_DIRECTORY" "$RELEASE_DIRECTORY"
        BUILD_DIRECTORY=""
    fi
else
    [[ -d "$RELEASE_DIRECTORY" ]] || fail "No existe la release solicitada para rollback."
    [[ -f "$RELEASE_DIRECTORY/.deploy-commit" ]] \
        || fail "La release de rollback no tiene un marcador de deploy válido."
    [[ "$(<"$RELEASE_DIRECTORY/.deploy-commit")" == "$COMMIT" ]] \
        || fail "El marcador de la release de rollback no coincide con el SHA."
fi

TARGET_RELEASE="$(readlink -f -- "$RELEASE_DIRECTORY")" \
    || fail "No se pudo resolver el directorio de la release."
[[ -f "$TARGET_RELEASE/public/index.php" ]] \
    || fail "La release no contiene public/index.php."

if [[ "$TARGET_RELEASE" == "$PREVIOUS_RELEASE" ]]; then
    health_check || fail "La release actual no pasó el health check."
    printf 'La release %s ya estaba activa y pasó el health check.\n' "$COMMIT"
    exit 0
fi

switch_current "$TARGET_RELEASE"

if ! health_check; then
    printf 'Falló el health check de %s; restaurando %s.\n' \
        "$COMMIT" "$PREVIOUS_RELEASE" >&2
    switch_current "$PREVIOUS_RELEASE"

    if ! health_check; then
        printf 'WARNING: rollback activated, but the previous health check also failed.\n' >&2
    else
        printf 'Rollback a la versión anterior verificado.\n' >&2
    fi

    exit 1
fi

printf 'Release %s activada y verificada correctamente.\n' "$COMMIT"
