<?php

/*
 * Modo demo: si el servidor no tiene archivo .env (por ejemplo, justo después de un
 * despliegue Git en Hostinger), la app arranca sola con una base SQLite y datos FICTICIOS.
 * En cuanto exista un .env (instalación real con MySQL), este modo se desactiva solo.
 */

$base = dirname(__DIR__);
if (file_exists($base.'/.env') || getenv('APP_KEY')) {
    return;
}

$dir = $base.'/storage/app/demo';
if (! is_dir($dir)) {
    @mkdir($dir, 0775, true);
}
$keyFile = $dir.'/app.key';
if (! is_file($keyFile)) {
    file_put_contents($keyFile, 'base64:'.base64_encode(random_bytes(32)), LOCK_EX);
}
$db = $dir.'/demo.sqlite';
if (! is_file($db)) {
    touch($db);
}

$vars = [
    'APP_DEMO' => 'true',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_KEY' => trim(file_get_contents($keyFile)),
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $db,
    'SESSION_DRIVER' => 'file',
    'CACHE_STORE' => 'file',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'log',
    'LOG_CHANNEL' => 'single',
];
foreach ($vars as $k => $v) {
    putenv("$k=$v");
    $_ENV[$k] = $v;
    $_SERVER[$k] = $v;
}
