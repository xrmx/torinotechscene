<?php

require_once('vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;

function readHostedGroups() {
        $data = Yaml::parse(file_get_contents('_data/groups.yml'));
        return $data;
}

/*
        Preso da
        http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string#2955878
*/
function slugify($text) {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text))
                return 'n-a';

        return $text;
}

function saveEvent($object) {
        $slug = slugify($object->title);
        $date = date('Y-m-d', $object->time);
        $filename = sprintf('_posts/%s-%s.markdown', $date, $slug);

        if (file_exists($filename))
                return false;

        $fulldate = date('Y-m-d G:i:s', $object->time);
        $groupname = $object->group['title'];

        $data =<<<DATA
---
title: $object->title
location: $object->location
date: $fulldate
host: $groupname
---

$object->content

DATA;

        file_put_contents($filename, $data);
        return true;
}
