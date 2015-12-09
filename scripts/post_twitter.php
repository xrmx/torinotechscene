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

foreach($events as $event) {
        $testable = sprintf("%s %s\n", $event['date'], $event['title']);
        if (array_search($testable, $cached) !== false)
                continue;

        file_put_contents($cachefile, $testable, FILE_APPEND);
        $date = date('d/m: ', strtotime($event['date']));

        $append = '';
        $group = groupByName($event['host']);
        if ($group != null && isset($group['twitter']))
                $append = sprintf(' /cc @' . $group['twitter']);

        $limit = 140 - (strlen($date) + strlen($append));
        $title = substr($event['title'], 0, $limit);

        $tweet = sprintf('%s%s%s', $date, $title, $append);
        $connection->post("statuses/update", array("status" => $tweet));
        sleep(1);
}
