#!/bin/sh

start=`pwd`

git clone git://github.com/mapbender/mapbender-starter.git mapbender-starter
cd mapbender-starter
git submodule update --init --recursive
cd application/mapbender/src/Mapbender/CoreBundle/Resources/public/mapquery/lib
./getdepsh.sh

cd $start

