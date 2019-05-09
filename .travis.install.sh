#! /bin/bash

set -e

originalDirectory=$(pwd)

wget "https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz"
tar -zxf "$MW.tar.gz"
rm "$MW.tar.gz"
mv "mediawiki-$MW" .mediawiki

cd .mediawiki

mediawikiDirectory=$(pwd)

# composer hooks will fail due to some db access attempt, we do not care
# about that and want to continue
composer install || true

if [[ $DBTYPE = 'mysql' ]]
then
	mysql -e 'DROP DATABASE IF EXISTS its_a_mw; CREATE DATABASE its_a_mw;'
fi

php maintenance/install.php --dbtype "$DBTYPE" --dbuser root --dbname its_a_mw --dbpath "$(pwd)" --pass nyan TravisWiki admin --scriptpath /TravisWiki

cd vendor
mkdir wikibase
cd wikibase

ln -s "$originalDirectory" mediawiki-term-store

cd mediawiki-term-store

composer instal

cd "$mediawikiDirectory"

{
    echo 'include_once( __DIR__ . "/vendor/wikibase/mediawiki-term-store/vendor/autoload.php" );'

    echo 'error_reporting(E_ALL| E_STRICT);'
    echo 'ini_set("display_errors", 1);'
    echo '$wgShowExceptionDetails = true;'
    echo '$wgDevelopmentWarnings = true;'
    echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );"
} >> LocalSettings.php

php maintenance/update.php --quick
