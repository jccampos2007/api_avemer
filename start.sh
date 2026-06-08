#!/bin/bash

# 1. Buscar de forma dinámica dónde instaló Nixpacks el binario de PHP-FPM
PHP_FPM_BIN=$(which php-fpm || which php81-fpm || which php82-fpm || which php83-fpm || find /nix/store -name "php-fpm" -type f -executable 2>/dev/null | head -n 1)

if [ -z "$PHP_FPM_BIN" ]; then
    echo "❌ Error: No se encontró ningún binario de PHP-FPM en el contenedor."
    exit 1
fi

echo "✅ Ejecutando PHP-FPM desde: $PHP_FPM_BIN"

# 2. Arrancar PHP-FPM con nuestra configuración personalizada en segundo plano
$PHP_FPM_BIN -y /app/php-fpm.conf -R &

# Esperar un segundo a que el puerto 9000 responda
sleep 1

# 3. Arrancar Nginx en primer plano para mantener vivo el contenedor
echo "🚀 Iniciando Nginx..."
nginx -c /app/nginx.conf -g 'daemon off;'