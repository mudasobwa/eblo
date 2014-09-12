#!/bin/sh

which bower || exit 1
which composer || exit 2

echo 'Initializing submodules...'
git submodule init

echo 'Bootstrapping node, bower and composer...'
npm install
bower install
cd app
composer install
cd -

echo 'Done.'
