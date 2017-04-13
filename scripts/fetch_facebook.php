<?php

/*
        Monitora gli eventi pubblicati sulle pagine Facebook dei gruppi aderenti
        (cfr. parametro fbpage in _data/groups.yml).
        Da invocare nello script di aggiornamento come
        php scripts/fetch_facebook.php
*/

require_once('utils.php');

$url = sprintf("https://graph.facebook.com/oauth/access_token?client_id=%s&client_secret=%s&grant_type=client_credentials", $conf['facebook']['client_id'], $conf['facebook']['client_secret']);
$resp = doGet($url);
$resp = json_decode($resp);
if ($resp == null || isset($resp->access_token) == false)
        exit();

$token = $resp->access_token;
$groups = readHostedGroups();

foreach($groups as $group) {
        if (isset($group['fbpage'])) {
                $url = sprintf("https://graph.facebook.com/%s/events?access_token=%s", $group['fbpage'], $token);

                $resp = doGet($url);
                $resp = json_decode($resp);
                if (isset($resp->data) == false)
                        continue;

                foreach($resp->data as $event) {
                        if (isset($event->place)) {
                                $location = $event->place->name;
                                if (isset($event->place->location))
                                        $location .= ', ' . $event->place->location->street;
                        }
                        else {
                                $location = '';
                        }

                        $obj = (object)[
                                'title' => $event->name,
                                'content' => isset($event->description) ? $event->description : '',
                                'time' => strtotime($event->start_time),
                                'location' => $location,
                                'url' => sprintf('https://www.facebook.com/events/%s/', $event->id),
                                'group' => $group
                        ];

                        saveEvent($obj);
                }
        }
}
