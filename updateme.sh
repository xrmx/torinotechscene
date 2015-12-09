#!/bin/sh

git pull

php scripts/fetch_facebook.php

if [ -e /usr/local/rvm/scripts/rvm ]
then
	source /usr/local/rvm/scripts/rvm
fi

jekyll build
