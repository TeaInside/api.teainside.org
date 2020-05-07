<?php

if (PHP_SAPI !== "cli") {
    echo "Must be run in CLI!\n";
    exit;
}

define("CORONA_STATS_STORAGE", __DIR__."/data");

require __DIR__."/CoronaStatistic.php";

$token = "448907482:AAH4CzKcZcoTWAMBxnLCah1eROlYprqfskM";
$listChatId = [
    -1001128970273, // Private Cloud
    // -1001377289579, // /\
    // -1001120283944,
    // -1001162202776,
    // -1001076005465, // Scrape ID
];

while (true) {
    echo "Checking corona...\n";
    $st = new CoronaStatistic;
    $st->scrape();
    $now = strtotime(gmdate("Y-m-d H:i:s"));
    echo "Scraped!\n";

    $file = CORONA_STATS_STORAGE."/indonesia_data.json";
    if (file_exists($file)) {
        $jsonOldData = file_get_contents($file);
    } else {
        $jsonOldData = "[]";
    }

    $newData = $st->getNewCases("Indonesia");
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $oldData = json_decode($jsonOldData, true);
    if (!is_array($oldData)) {
        $oldData = [];
    }

    $dataToReport = [];
    foreach ($newData as $k => $v) {
        if (isset($oldData[$k])) {
            $oldData[$k] = $v;
        } else {
            $dataToReport[$k] = $v;
        }
    }

    $allData = array_merge($oldData, $newData);
    file_put_contents($file, json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $c = count($dataToReport);
    if ($c === 0) {
        echo "No changes!\n";
        goto skip;
    }

    $text = "<b>[Coronavirus Update (Indonesia) ({$c} updates)]</b>\n";
    $j = 0;
    foreach ($dataToReport as $k => $v) {
        $text .= "{$j}. ({$v["date"]}) {$v["info"]} {$v["source"]} [rhash:{$k}]\n\n";
        $j++;
    }

    foreach ($listChatId as $v) {
        echo "Sending to ".$v."...\n";
        $ch = curl_init("https://api.telegram.org/bot".$token."/sendMessage");
        curl_setopt_array($ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(
                    [
                        "chat_id" => $v,
                        "text" => $text,
                        "parse_mode" => "HTML"
                    ]
                )
            ]
        );
        $o = curl_exec($ch);
        curl_close($ch);
        echo $o;
    }
    echo "There are some changes!\n";

    skip:
    sleep(60);
}
