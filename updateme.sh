#!/bin/sh

git pull

php scripts/fetch_facebook.php
php scripts/fetch_eventbrite.php

if [ -e /usr/local/rvm/scripts/rvm ]
then
	source /usr/local/rvm/scripts/rvm
fi

jekyll build

php scripts/post_twitter.php

date > lastupdate.txt
