#!/bin/sh
set -eu

php artisan migrate --force --no-interaction
