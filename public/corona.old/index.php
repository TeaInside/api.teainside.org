<?php

define("CORONA_STATS_STORAGE", __DIR__."/data");

require __DIR__."/CoronaStatistic.php";

$st = new CoronaStatistic;
$now = strtotime(gmdate("Y-m-d H:i:s"));


if (isset($_GET["all"])) {
    $data = $st->getAllCountry();
} else {
    $closure = function ($data, int $type = 0, $country = null) use ($st) {
        if (
            ((!isset($data["scraped_at"])) || (abs($now - $data["scraped_at"]) > 500))
        ) {
            $st->scrape();
            switch ($type) {
                case 0:
                    $data = $st->getCountry($country);
                    break;
                case 1:
                    $data = $st->getGlobal();
                    break;
                case 2:
                    $data = [];
                    foreach ($country as $k => $v) {
                        $data[] = $st->getCountry($country);
                    }
                    break;
                case 3:
                    $data = $st->getAllCountry();
                    break;
            }
        } else if ($type == 2) {
            $data = [];
            foreach ($country as $k => $v) {
                $data[] = $st->getCountry($country);
            }
        }
        return $data;
    };

    if (isset($_GET["country"])) {
        if (is_string($_GET["country"])) {
            $data = $closure($st->getCountry($_GET["country"]), 0);
        } else if (is_array($_GET["country"])) {
            $data = $closure(null, 1);
        }
    }
    


    if (($data["cmt"] == 0) && ($data["fst"] == 0) && ($data["sdt"] == 0)) {
        $data = ["data" => "not_found"];
    }
}

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
echo json_encode($data);
