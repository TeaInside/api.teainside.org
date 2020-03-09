<?php

define("CORONA_STATS_STORAGE", __DIR__."/data");

require __DIR__."/CoronaStatistic.php";

$st = new CoronaStatistic;
$now = strtotime(gmdate("Y-m-d H:i:s"));

if (isset($_GET["country"])) {
    $data = $st->getCountry($_GET["country"]);
    if (abs($now - $data["scraped_at"]) > 3600) {
        $st->scrape();
        $data = $st->getCountry($_GET["country"]);
    }
} else {
    $data = $st->getGlobal();
    if (isset($data["scraped_at"]) && (abs($now - $data["scraped_at"]) > 3600)) {
        $st->scrape();
        $data = $st->getGlobal();
    }
}

header("Content-Type: application/json");
echo json_encode($data);
