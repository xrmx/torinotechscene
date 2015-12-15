#!/bin/bash

git pull

php scripts/fetch_facebook.php
php scripts/fetch_eventbrite.php
php scripts/fetch_meetup.php

if [ -e /usr/local/rvm/scripts/rvm ]
then
	source /usr/local/rvm/scripts/rvm
fi

jekyll build --future

php scripts/post_twitter.php

date > lastupdate.txt
