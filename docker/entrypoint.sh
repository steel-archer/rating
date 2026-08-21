#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --no-interaction

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- apache2-foreground "$@"
fi

exec "$@"
