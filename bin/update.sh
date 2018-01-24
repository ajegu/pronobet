#!/bin/sh

git pull origin master
composer update

php bin/console doctrine:schema:update --force
sudo php bin/console cache:clear --no-warmup --env=prod