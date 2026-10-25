#!/bin/sh
# Render y Railway indican en $PORT el puerto donde debe escuchar el servidor.
set -e
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
export PORT
exec apache2-foreground
