#!/usr/bin/env bash
# Instala o actualiza Aportes Simple en Hostinger (plan Business con SSH).
# Uso: desde la carpeta del sitio (donde está artisan):  bash scripts/instalar-hostinger.sh
set -euo pipefail
cd "$(dirname "$0")/.."

PHP=${PHP:-php}
if ! $PHP -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
  echo "Se necesita PHP 8.3 o superior. Cámbialo en hPanel > Avanzado > Configuración de PHP y vuelve a correr este script."; exit 1
fi
COMPOSER=$(command -v composer2 || command -v composer || true)
if [ -z "$COMPOSER" ]; then
  echo "Descargando Composer..."; curl -sS https://getcomposer.org/installer | $PHP -- --quiet; COMPOSER="$PHP composer.phar"
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction
# El hosting deshabilita proc_open en algunos PHP; por eso esto no va en los scripts de composer.json.
$PHP artisan package:discover --ansi

if [ ! -f .env ]; then
  echo "== Primera instalación. Escribe los datos de la base de datos que creaste en hPanel =="
  read -rp "Nombre de la base de datos (ej. u123456789_aportes): " DB_DATABASE
  read -rp "Usuario de la base de datos: " DB_USERNAME
  read -rsp "Contraseña de la base de datos: " DB_PASSWORD; echo
  read -rp "Dirección de la app (ej. https://app.aportecimple.com): " APP_URL
  read -rp "Tu nombre (administrador): " ADMIN_NAME
  read -rp "Tu correo (administrador): " ADMIN_EMAIL
  read -rsp "Contraseña para entrar a la app (mínimo 8 caracteres): " ADMIN_PASSWORD; echo
  for v in "$DB_DATABASE" "$DB_USERNAME" "$DB_PASSWORD" "$APP_URL" "$ADMIN_NAME" "$ADMIN_EMAIL" "$ADMIN_PASSWORD"; do
    case "$v" in *\'*) echo "Ningún dato puede tener comilla simple ('). Corre el script de nuevo."; exit 1;; esac
  done
  if [ ${#ADMIN_PASSWORD} -lt 8 ]; then echo "La contraseña de la app debe tener mínimo 8 caracteres."; exit 1; fi
  cp .env.example .env
  trap 'echo "La instalación falló. Se borra el .env para que puedas intentarlo de nuevo."; rm -f .env' ERR
  # Escribe CLAVE='valor' en .env usando PHP (comillas simples = el valor se guarda tal cual).
  set_env() { K="$1" V="$2" $PHP -r '
    $k = getenv("K"); $v = getenv("V");
    if (str_contains($v, "\x27")) { fwrite(STDERR, "El valor de $k no puede tener comilla simple (\x27). Corre el script de nuevo.\n"); exit(1); }
    $f = file_get_contents(".env"); $line = $k . "=\x27" . $v . "\x27";
    $n = preg_replace("/^#?\\s?" . preg_quote($k, "/") . "=.*$/m", addcslashes($line, "\\$"), $f, 1, $c);
    file_put_contents(".env", $c ? $n : rtrim($f) . "\n" . $line . "\n");'; }
  set_env APP_ENV production; set_env APP_DEBUG false; set_env APP_URL "$APP_URL"
  set_env DB_CONNECTION mysql; set_env DB_HOST 127.0.0.1; set_env DB_PORT 3306
  set_env DB_DATABASE "$DB_DATABASE"; set_env DB_USERNAME "$DB_USERNAME"; set_env DB_PASSWORD "$DB_PASSWORD"
  set_env ADMIN_NAME "$ADMIN_NAME"; set_env ADMIN_EMAIL "$ADMIN_EMAIL"; set_env ADMIN_PASSWORD "$ADMIN_PASSWORD"
  set_env SESSION_SECURE_COOKIE true
  $PHP artisan key:generate --force
  $PHP artisan migrate --force --seed
  # La contraseña del administrador ya quedó guardada (cifrada) en la base de datos; se borra del .env.
  sed -i 's|^ADMIN_PASSWORD=.*|ADMIN_PASSWORD=|' .env
  trap - ERR
else
  $PHP artisan migrate --force
fi

chmod -R ug+rw storage bootstrap/cache
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
echo "Listo. Abre $(grep ^APP_URL= .env | cut -d= -f2- | tr -d '\"') e inicia sesión."
