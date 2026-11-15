# CI/CD del backend AlquilER

## Estado

El workflow y el script de despliegue están preparados; CD sigue desactivado
(`CD_ENABLED` ausente o `false`). El PR #46 está abierto contra `master`.
PHPUnit, PHP Lint y la simulación de activación y rollback pasaron en GitHub
Actions; todavía no se ha desplegado una release nueva.

Preparación actual de AlwaysData:

- `~/www/alquiler-backend-cd/` existe. La copia inicial se conserva en
  `shared-initial-snapshot/`.
- Los seis targets de `shared/` son symlinks a los datos que usa la aplicación
  antigua. Es una transición deliberada para no perder logs/uploads escritos
  durante el cambio; hay que conservar el árbol antiguo mientras `shared`
  dependa de él.
- `current` apunta al backend antiguo, como bootstrap y destino de rollback.
- El DocumentRoot es
  `/home/alquiler/www/alquiler-backend-cd/current/public`. El health check
  confirmó que Apache/PHP-FCGI siguen el symlink.
- El hook `post-receive` está conservado, pero no es ejecutable; se guardó una
  copia con sufijo `.backup-cd-*`.

No habilitar CD hasta completar la verificación final. El primer `push` a
`master` con `CD_ENABLED=true` realizará el despliegue real.

Esta separación permite integrar el código de CI/CD sin que un push a `master`
intente publicar antes de que el servidor esté listo.

## Flujo

1. Los pull requests dirigidos a `dev` o `master` ejecutan PHPUnit Unit y PHP
   Lint.
2. Las actualizaciones de `dev` y `master` también ejecutan CI.
3. Solo un `push` a `master` puede satisfacer la condición del job `Deploy`.
4. El job depende de PHPUnit y PHP Lint. Además exige que la variable de
   entorno GitHub `CD_ENABLED` sea exactamente `true`.
5. Actions hace checkout del historial completo para que el commit aprobado
   conserve su relación de ancestros y pueda actualizar `master` de AlwaysData
   mediante fast-forward. Luego envía el SHA exacto de GitHub al bare repository
   por SSH.
6. Actions transmite `scripts/deploy/alwaysdata-release.sh` por SSH. El script
   comprueba que el SHA sea el `master` recibido, prepara una release nueva,
   instala dependencias, comprueba los requisitos de plataforma y la sintaxis
   PHP, cambia `current` y verifica el endpoint de salud.
7. El script devuelve un código de error si no logra desplegar o verificar.
   Si falla el health check posterior a la activación, intenta restaurar el
   destino anterior de `current`; el job queda fallido aunque el rollback
   funcione.
   Si falla la preparación, el directorio oculto `.build-<sha>.*` se conserva
   fuera del DocumentRoot para diagnóstico y no se activa.
8. CI también ejecuta una prueba aislada del script que simula una activación
   correcta y un health check fallido con rollback; no se conecta a AlwaysData.

El hook `post-receive` anterior publicaba mediante checkout y Composer. Ya está
deshabilitado para evitar que el `git push` de Actions publique dos veces. Su
copia de respaldo permite restaurarlo si hay que abortar antes de la primera
release.

## Runtime de CI

AlwaysData confirmó por SSH PHP 8.4.27 y Composer 2.9.8; también están
disponibles `git`, `tar`, `curl` y `xargs`. El workflow usa PHP 8.4 para
acercar la validación al runtime de producción. PHPUnit es el suite Unit
existente; el workflow no ejecuta pruebas de integración contra la base de
producción.

Antes de fusionar el PR, el bare repository confirmó como `master` el commit
`d7f836a4055393080bfa0ef9d042ea28b8c07bb9`. El hook no es ejecutable. El job
de Actions actualizará el bare `master` y ejecutará el script por SSH.

## Rutas del servidor

Se eligió una raíz candidata bajo `~/www/alquiler-backend-cd/`, separada del
DocumentRoot actual y dentro de un árbol `www` ya accesible por el sitio:

```text
~/www/alquiler-backend-cd/
├── shared/
├── releases/<sha>/
└── current -> releases/<sha>
```

El DocumentRoot es
`~/www/alquiler-backend-cd/current/public`. Ya se comprobó el symlink en el
filesystem y el health check HTTP siguió funcionando después del cambio.
AlwaysData reporta Apache, `mod_fcgid` y `FollowSymLinks` habilitado.

Los targets compartidos que exige el script:

```text
shared/.env
shared/debug.log
shared/public/uploads/
shared/storage/logs/
shared/storage/errores.log
shared/storage/emails.log
```

En el primer despliegue estos targets enlazan a las rutas de la instalación
antigua para que ambas versiones usen el mismo estado mutable. La copia inicial
permanece en `shared-initial-snapshot/`. No eliminar la instalación antigua ni
la instantánea; trasladar los datos físicamente a `shared/` debe hacerse por
separado y con una sincronización controlada.

`storage/cache` permanece dentro de cada release porque es regenerable.
`storage/performance-dashboard` también es estado local/regenerable del
mecanismo de captura; el dashboard se usa localmente y no importa sesiones de
producción. El rate limiter no está configurado con `RATE_LIMIT_STORAGE_PATH`
y usa archivos temporales del sistema. El log plano
`storage/logs/performance.jsonl` debe conservarse dentro del directorio
compartido de logs como historial legacy; no se mezcla con
`storage/logs/production/performance.jsonl`.

No usar `rsync --delete`, `git clean` ni una limpieza recursiva sobre la
instalación actual. Mantener originales y respaldos hasta validar deploy y
rollback. Proteger `.env` y logs con permisos privados compatibles con el
usuario/grupo real de PHP-FCGI.

## Configuración requerida en GitHub

Crear Environment `production` en **Settings → Environments** y añadir:

**Secrets**

- `ALWAYSDATA_SSH_KEY`: clave privada dedicada a Actions, sin passphrase
  interactiva. No reutilizar claves personales.
- `ALWAYSDATA_KNOWN_HOSTS`: línea `known_hosts` del host verificada por una
  fuente confiable de AlwaysData.

**Repository variable**

- `CD_ENABLED`: mantener ausente o distinto de `true` mientras se prepara el
  servidor; poner `true` solo para habilitar CD.

**Environment variables** del entorno `production`

- `ALWAYSDATA_HOST`: `ssh-alquiler.alwaysdata.net`.
- `ALWAYSDATA_USER`: `alquiler`.
- `PRODUCTION_HEALTH_URL`: URL HTTPS completa del endpoint `/api/health`.

La clave pública correspondiente debe autorizarse en AlwaysData. Investigar
si el hosting permite limitar sus permisos. Nunca imprimir ni copiar la clave
privada o el contenido de `.env` en logs, issues o conversaciones.

## Preparación de AlwaysData antes de habilitar CD

1. Confirmar con soporte/una prueba aislada que Apache sirve
   `current/public` mediante symlink y ejecuta PHP-FCGI en su destino.
2. Confirmado por SSH: PHP 8.4.27, Composer 2.9.8 y los comandos `git`, `tar`,
   `curl` y `xargs`. El primer workflow verificará la autenticación no
   interactiva y la disponibilidad de estos comandos antes de activar una
   release. El script usa Composer/PHP CLI del servidor.
3. Respaldar la instalación activa, `.env`, `debug.log`, uploads, logs actuales
   y legacy, `errores.log`, `emails.log` y el hook actual. No mostrar
   contenidos sensibles.
4. **Completado:** crear `~/www/alquiler-backend-cd/releases` y `shared`.
   Conservar la copia inicial en `shared-initial-snapshot/`; los targets vivos
   de `shared/` son symlinks a los datos antiguos para que escrituras
   concurrentes no se pierdan.
5. Mantener `storage/cache` fuera de shared. No trasladar
   `public/storage/cache` (directorio legacy sin uso detectado) ni el estado de
   sesiones del dashboard.
6. **Completado:** `current` apunta a la aplicación anterior y sirve como
   destino de rollback.
7. **Completado:** el PR #46 pasa los checks. La nueva release se verificará
   mediante el primer deploy real, no antes de apuntar el DocumentRoot a
   `current/public`.
8. **Completado:** DocumentRoot usa
   `~/www/alquiler-backend-cd/current/public`; el health check funciona. Si una
   verificación posterior falla, volver al DocumentRoot anterior.
9. **Completado:** `post-receive` se conservó como copia, pero se retiró su bit
   ejecutable. No reactivarlo al habilitar Actions.
10. **Completado salvo activación:** secrets y variables del Environment
    `production` están configurados. Mantener `CD_ENABLED` desactivada hasta
    estar listo para fusionar el PR #46 y desplegar.

Como no hay staging, el primer cambio real es directamente en producción.
Programarlo, verificar copias y rollback, no incluir migraciones de base de
datos destructivas y conservar el árbol previo durante la validación.
Tras diagnosticar una preparación fallida, retirar solo el directorio `.build`
específico de ese intento; no usar comodines amplios ni limpiar todo `releases/`.

## Rollback manual

El script acepta `rollback <sha> <health-url>` y solo activa una release que
tenga marcador `.deploy-commit` coincidente. Se ejecuta por SSH enviando el
script por stdin, con la clave y el `known_hosts` configurados:

```sh
ssh usuario@host "bash -s -- rollback <sha-completo> <url-https-health>" \
  < scripts/deploy/alwaysdata-release.sh
```

Usar los mismos controles SSH que Actions. No hacer force-push para revertir
producción. El rollback del código no revierte cambios de esquema de base de
datos, mensajes externos ni otros efectos secundarios.

## Reglas de rama y cierre del bypass

En GitHub, proteger `master` para requerir pull request y los checks
`PHPUnit (Unit)` y `PHP Lint`; bloquear force-push y borrado. No exigir el job
Deploy como requisito del pull request porque CD ocurre después del merge.

Después de probar Actions, restringir el acceso de escritura directa al bare
repository de AlwaysData para que el camino habitual no pueda saltarse CI.
Mantener una vía administrativa de recuperación controlada.

## Criterios de finalización

- CI de PHP 8.4 pasa en PR y en `master`.
- Fallar PHPUnit o PHP Lint impide que CD corra.
- CD solo corre en push a `master` cuando `CD_ENABLED=true`.
- El SHA desplegado es idéntico al commit validado por CI.
- El despliegue prepara una release aparte y no sobrescribe la activa durante
  la instalación.
- Fallo de preparación no cambia `current`; fallo de health check intenta
  volver a la versión anterior y marca Actions como fallido.
- `.env`, uploads y logs sobreviven a los cambios de release; caché no se
  comparte.
- `/api/health` responde correctamente y el procedimiento de rollback está
  ensayado.
- La escritura directa a AlwaysData no es un camino habitual que evite CI/CD.
