#!/bin/bash

set -e
set -x

BASEDIR=$( dirname $( realpath $0 ) )

cd $BASEDIR

for FILE in "ts"
do
    if [[ -f "$FILE" ]]; then
        rm -rf $FILE
    fi
    ln -sf ../../../$FILE .
done

mkdir -p public/js/lib

wget https://code.jquery.com/jquery-3.6.0.js -O public/js/jquery.js

npm install
node node_modules/typescript/bin/tsc
node node_modules/webpack/bin/webpack.js

echo "Done."
