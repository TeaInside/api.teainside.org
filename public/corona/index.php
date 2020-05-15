<?php

define("CORONA_STATS_STORAGE", __DIR__."/data");
require __DIR__."/CoronaStatistic.php";
use CoronaStatistic\CoronaStatistic;

$st = new CoronaStatistic;
$wm = $st->worldometers();
if (isset($_GET["country"])) {
    $data = $wm->getCountryData(is_array($_GET["country"]) ? $_GET["country"] : [$_GET["country"]]);
} else if (isset($_GET["all"])) {
    $data = $wm->getAllCountriesData();
} else {
    $data = $wm->getGlobalData();
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
echo json_encode($data);
