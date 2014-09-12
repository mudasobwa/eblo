#!/bin/sh

which bower || exit 1
which composer || exit 2

npm install
bower install
cd app
composer install
cd -
