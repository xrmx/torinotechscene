<?php

/*
        Scrive sui canali telegram i prossimi eventi, tenendo traccia di quelli che già sono stati
        notificati
        Da invocare nello script di aggiornamento come
        php scripts/post_telegram.php
*/

require_once('utils.php');

$cachefile = 'scripts/cache/telegram.txt';
$cached = file($cachefile);
if (!$cached)
	$cached = array();

$events = futureEvents();

$channels = $conf["telegram"]["channels"];
$api_url = sprintf("https://api.telegram.org/bot%s/sendMessage", $conf["telegram"]["token"]);

foreach($events as $event) {
	$testable = sprintf("%s%s\n", $event['link'], $event['date']);
	if (array_search($testable, $cached) !== false)
		continue;

	file_put_contents($cachefile, $testable, FILE_APPEND);
	$date = date('d/m: ', strtotime($event['date']));

	$append_url = sprintf(' %s', $event['link']);

	/* Even if this is not twitter let's keep messages short */
	$limit = 280 - strlen($date);
	$title = substr($event['title'], 0, $limit);

	$msg = sprintf('%s%s%s', $date, $title, $append_url);
	foreach($channels as $channel) {
		$data = array(
			"chat_id" => $channel,
			"text" => $msg,
		);
		doPost($api_url, http_build_query($data));
	}
	sleep(1);
}

