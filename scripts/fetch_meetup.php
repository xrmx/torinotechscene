<?php

/*
	Monitora gli eventi pubblicati sugli accounts Eventbrite dei gruppi aderenti
	(cfr. parametro source-eventbrite in _data/groups.yml).
	Da invocare nello script di aggiornamento come
	php scripts/fetch_eventbrite.php
*/

require_once('utils.php');

$groups = readHostedGroups();

foreach($groups as $group) {
	if (isset($group['source-meetup'])) {
		$url = sprintf("https://api.meetup.com/2/events?group_urlname=%s", $group['source-meetup']);
		$resp = doGet($url);
		$resp = json_decode($resp);

		/*
			Qui si assume che un gruppo pubblichi un solo evento alla volta.
			Se ne esiste più di uno, quando il primo dell'elenco sarà passato
			(e dunque non apparirà più in questo elenco), implicitamente il
			prossimo diventerà il primo della lista e sarà pubblicato.
		*/

		if (empty($resp->results))
			continue;

		$event = $resp->results[0];

		if (property_exists($event, 'venue'))
			$location = sprintf('%s, %s', $event->venue->name, $event->venue->address_1);
		else
			$location = 'Sconosciuto';

		$obj = (object)[
			'title' => $event->name,
			'content' => $event->description,
			'time' => $event->time / 1000,
			'location' => $location,
			'url' => $event->event_url,
			'group' => $group
		];

		saveEvent($obj);
	}
}

