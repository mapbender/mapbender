#!/bin/bash

while getopts "u" o; do
  case "${o}" in
    u)
      update=1
      ;;
  esac
done
shift $((OPTIND-1))

target=$1
origdir=$(pwd)

if(test -z $target)
then
    cat <<EOF
Usage: ./bootstrap.sh [-u] target-dir

target-dir: directory to install into
        -u: do an update only (no git clone)
EOF

    exit 1
fi

if(test -z $(which git)) then
    echo No git installed.
    exit 1
fi

if(test -z $(which php))
then
    echo No php installed.
    exit 1
fi

function installMapbender {
  cd $origdir
  if(test -d $target)
  then
      echo Target directory directory exists.
      exit 1
  fi

  git clone -b develop git://github.com/mapbender/mapbender-starter $target
  cd $target
  git submodule update --init --recursive

  cd application

  cp app/config/parameters.yml.dist app/config/parameters.yml
  ../composer.phar install
  app/console doctrine:schema:update --force
  cd $origdir
}

function updateMapbender {
  cd $origdir
  cd $target
  cd application

  ../composer.phar install

  app/console doctrine:schema:update --force
  app/console doctrine:fixtures:load --fixtures=./mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/ --append
  app/console fom:user:resetroot --username="root" --password="root" --email="root@example.com" --silent

  cd $origdir
}

if(test -z $update)
then
    installMapbender
    updateMapbender
else
  updateMapbender
fi
