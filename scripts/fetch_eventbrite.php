<?php

/*
        Monitora gli eventi pubblicati sugli accounts Eventbrite dei gruppi aderenti
        (cfr. parametro source-eventbrite in _data/groups.yml).
        Da invocare nello script di aggiornamento come
        php scripts/fetch_eventbrite.php
*/

require_once('utils.php');

$token = $conf['eventbrite']['token'];
$groups = readHostedGroups();

foreach($groups as $group) {
        if (isset($group['source-eventbrite'])) {
                $url = sprintf("https://www.eventbriteapi.com/v3/events/search/?token=%s&organizer.id=%s", $token, $group['source-eventbrite']);
                $resp = doGet($url);
                $resp = json_decode($resp);

                foreach($resp->events as $event) {
                        /* Permettiamo di filtrare gli eventi in base ad un testo presente nel titolo.
                         * Utile quando usiamo lo stesso account per location diverse */
                        if (isset($group['eb-filter-title'])) {
                                if (strpos($event->name->text, $group['eb-filter-title']) === FALSE) {
                                        continue;
                                }
                        }

                        $url = sprintf("https://www.eventbriteapi.com/v3/venues/%s/?token=%s", $event->venue_id, $token);
                        $venue_resp = doGet($url);
                        $venue_resp = json_decode($venue_resp);
                        $location = sprintf('%s, %s', $venue_resp->name, $venue_resp->address->address_1);

                        $obj = (object)[
                                'title' => $event->name->text,
                                'content' => $event->description->text,
                                'time' => strtotime($event->start->local),
                                'location' => $location,
                                'url' => $event->url,
                                'group' => $group
                        ];

                        saveEvent($obj);
                }
        }
}
