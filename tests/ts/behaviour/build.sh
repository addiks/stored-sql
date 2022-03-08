#!/bin/bash

set -e
set -x

BASEDIR=$( dirname $( realpath $0 ) )

cd $BASEDIR

rm -rf ts
rm -rf public/twig

ln -sf $( realpath ../../../ts ) ts
ln -sf $( realpath ../../../twig ) public/twig
ln -sf $( realpath ../../../twig ) public/js/twig

mkdir -p public/js/lib

wget https://code.jquery.com/jquery-3.6.0.js -O public/js/jquery.js

npm install
node node_modules/typescript/bin/tsc
node node_modules/webpack/bin/webpack.js

echo "Done."
