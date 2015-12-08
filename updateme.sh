#!/bin/sh

git pull

php scripts/fetch_facebook.php

jekyll build
