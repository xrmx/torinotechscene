<?php

/*
        Twitta i prossimi eventi, tenendo traccia di quelli che già sono stati
        notificati
        Da invocare nello script di aggiornamento come
        php scripts/post_twitter.php
*/

require_once('utils.php');

use Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth(
	$conf['twitter']['consumer_key'],
	$conf['twitter']['consumer_secret'],
	$conf['twitter']['access_token'],
	$conf['twitter']['access_token_secret']
);

$cachefile = 'scripts/cache/tweeted.txt';
$cached = file($cachefile);

$events = futureEvents();
$url_length = 0;

foreach($events as $event) {
	$testable = sprintf("%s %s\n", $event['date'], $event['title']);
	if (array_search($testable, $cached) !== false)
		continue;

	file_put_contents($cachefile, $testable, FILE_APPEND);
	$date = date('d/m: ', strtotime($event['date']));

	if ($url_length == 0) {
		$twitter_conf = $connection->get("help/configuration");
		// Aggiungo + 3 caratteri per lo spazio occupato dal
		// trattino di separazione e spazi annessi
		$url_length = $twitter_conf->short_url_length_https + 3;
	}
	$append_url = sprintf(' - %s', $event['link']);

	$append = '';

	if (isset($event['host'])) {
		$group = groupByName($event['host']);
		if ($group != null && !empty($group) && isset($group['twitter']))
			$append .= sprintf(' /cc @' . $group['twitter']);
	}

	$limit = 280 - (strlen($date) + $url_length + strlen($append));
	$title = substr($event['title'], 0, $limit);

	$tweet = sprintf('%s%s%s%s', $date, $title, $append_url, $append);
	$connection->post("statuses/update", array("status" => $tweet));
	sleep(1);
}

