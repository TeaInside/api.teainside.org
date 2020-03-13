<?php

define("CORONA_STATS_STORAGE", __DIR__."/data");

require __DIR__."/CoronaStatistic.php";

$st = new CoronaStatistic;
$now = strtotime(gmdate("Y-m-d H:i:s"));

if (isset($_GET["all"])) {
    $data = $st->getAllCountry();
    if ((!isset($data["scraped_at"])) || (abs($now - $data["scraped_at"]) > 500)) {
        $st->scrape();
        $data = $st->getAllCountry();
    }
} else {
    if (isset($_GET["country"]) && is_string($_GET["country"])) {
        $data = $st->getCountry($_GET["country"]);
        if ((!isset($data["scraped_at"])) || (abs($now - $data["scraped_at"]) > 500)) {
            $st->scrape();
            $data = $st->getCountry($_GET["country"]);
        }
    } else {
        $data = $st->getGlobal();
        if ((!isset($data["scraped_at"])) || (abs($now - $data["scraped_at"]) > 500)) {
            $st->scrape();
            $data = $st->getGlobal();
        }
    }

    if (($data["cmt"] == 0) && ($data["fst"] == 0) && ($data["sdt"] == 0)) {
        $data = ["data" => "not_found"];
    }
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
echo json_encode($data);
