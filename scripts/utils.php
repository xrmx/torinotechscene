<?php

require_once('config.php');
require_once('vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;

function doGet($url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'TorinoTechScene'
        ));

        $resp = curl_exec($curl);
        curl_close($curl);
        return $resp;
}

function readHostedGroups() {
        $data = Yaml::parse(file_get_contents('_data/groups.yml'));
        return $data;
}

function groupByName($groupname) {
        $groups = readHostedGroups();

        foreach($groups as $group)
                if ($group['title'] == $groupname)
                        return $group;

        return null;
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

function eventMetadata($filename) {
        $contents = file('_posts/' . $filename);

        $parsable = [];

        for($i = 1; $i < count($contents); $i++) {
                if (substr($contents[$i], 0, 3) == '---')
                        break;

                $parsable[] = $contents[$i];
        }

        return Yaml::parse(join('', $parsable));
}

function futureEvents() {
        $today = date('Y-m-d');
        $todaylen = strlen($today);
        $files = array_diff(scandir('_posts/'), ['..', '.']);

        $ret = [];

        foreach($files as $f) {
                if (strncmp($today, $f, $todaylen) > 0)
                        continue;

                $ret[] = eventMetadata($f);
        }

        return $ret;
}

function findDuplicatedEvent($hash) {
        $files = array_diff(scandir('_posts/'), ['..', '.']);

        foreach($files as $f) {
                if (strpos($f, $hash) !== false)
                        return $f;
        }

        return false;
}

function saveEvent($object) {
        $hash = sha1($object->url);
        $slug = slugify($object->title);
        $date = date('Y-m-d', $object->time);

        /*
                Per evitare di duplicare eventi che vengono rinominati mettiamo l'hash
                nel nome per poterlo ricercare. Se lo troviamo riscriviamo quel post
                piuttosto che crearne uno nuovo.
         */
        $oldpost = findDuplicatedEvent($hash);
        if ($oldpost === false)
                $filename = sprintf('_posts/%s-%s-$s.markdown', $date, $slug, $hash);
        else
                $filename = sprintf('_posts/%s', $oldpost);

        /*
                A YAML non sembrano piacere i due punti...
        */
        $title = str_replace(':', '', $object->title);

        /*
                Duplico i newline per una migliore formattazione dei posts in homepage
        */
        $contents = str_replace("\n", "\n\n", $object->content);

        /*
                Per ignoti motivi, mettendo anche i secondi Jekyll trasforma
                sempre tutte le date sulla timezone UTC.
                Omettendoli, lascia i valori correnti.
        */
        $fulldate = date('Y-m-d G:i', $object->time);

        $groupname = $object->group['title'];

        $data =<<<DATA
---
title: $title
location: $object->location
date: $fulldate
host: $groupname
link: $object->url
---

$contents

DATA;

        file_put_contents($filename, $data);
        return true;
}
