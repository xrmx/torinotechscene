<?php

/*
        Monitora gli eventi pubblicati sugli accounts Eventbrite dei gruppi aderenti
        (cfr. parametro source-eventbrite in _data/groups.yml).
        Da invocare nello script di aggiornamento come
        php scripts/fetch_eventbrite.php
*/

require_once('utils.php');

$groups = readHostedGroups();

function textByNode($xpath, $node, $class)
{
        $entries = $xpath->query('.//*[contains(@class, "' . $class . '")]', $node);
        foreach ($entries as $entry) {
                return trim((string) $entry->nodeValue);
        }
}

/*
        Le date su Eventbrite sono nel formato
        sab, 27 mar 2021 10:00
        Ovvero:
        - giorno della settimana (non utile)
        - giorno del mese
        - mese, espresso con tre caratteri
        - anno
        - ora
*/
function manageDate($date)
{
        $date = str_replace("\n", ' ', $date);
        preg_match('/^([a-z]{3,}), *([0-9]{1,2}) *([a-z]{3,}) *([a-z]{4}) *([0-9:]*)$/', strtolower($date), $matches);
        $day = $matches[2];
        $year = $matches[4];
        $hour = $matches[5];

        $months = ['', 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
        $month = array_search($matches[3], $months);

        $d = sprintf('%s-%s-%s %s', $year, $month, $day, $hour);
        $time = strtotime($d);
        if ($time < time()) {
                $d = sprintf('%s-%s-%s %s', $year + 1, $month, $day, $hour);
                $time = strtotime($d);
        }

        return $time;
}

function manageContents($url)
{
        $resp = doGet($url);

        $doc = new DOMDocument();
        $doc->loadHTML($resp, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($doc);

        $entries = $xpath->query('.//*[@data-automation="listing-event-description"]');
        foreach ($entries as $entry) {
                return str_replace("\n", "<br>\n", trim((string) $entry->nodeValue));
        }
}

foreach($groups as $group) {
        if (isset($group['source-eventbrite'])) {
                try {
                        $url = sprintf("https://www.eventbrite.it/o/%s", $group['source-eventbrite']);
                        $resp = doGet($url);

                        $doc = new DOMDocument();
                        $doc->loadHTML($resp, LIBXML_NOERROR | LIBXML_NOWARNING);
                        $xpath = new DOMXPath($doc);

                        $entries = $xpath->query('//[contains(@class, "eds-event-card-content__primary-content")]//a[contains(@class, "eds-event-card-content__action-link")]');

                        foreach ($entries as $entry) {
                                try {
                                        $title = textByNode($xpath, $entry, 'eds-event-card__formatted-name--is-clamped');

                                        /* Facciamo filtrare gli eventi in base ad un testo nel titolo:
                                         * utile quando usiamo lo stesso account per location diverse */
                                        $filter = isset($group['eb-filter-title']) && $group['eb-filter-title'];
                                        if ($filter && strpos($title, $filter) === FALSE)
                                                    continue;

                                        $url = $entry->getAttribute('href');

                                        $obj = (object) [
                                                'title' => $title,
                                                'content' => manageContents($url),
                                                'time' => manageDate(textByNode($xpath, $entry, 'eds-evet-card-content__sub-title')),
                                                'location' => '',
                                                'url' => $url,
                                                'group' => $group
                                        ];

                                        saveEvent($obj);
                                }
                                catch(Exception $e) {
                                        echo "Fallita lettura evento Eventbrite a URL " . $url . "\n";
                                }
                        }

                        sleep(5);
                }
                catch(Exception $e) {
                        echo "Fallita lettura eventi Eventbrite per pagina " . $group['source-eventbrite'] . "\n";
                }
        }
}

