<?php

define("CORONA_STATS_STORAGE", __DIR__."/data");

require __DIR__."/CoronaStatistic.php";

$token = "448907482:AAH4CzKcZcoTWAMBxnLCah1eROlYprqfskM";
$listChatId = [-1001377289579, -1001120283944, -1001162202776];

while (true) {
    echo "Checking corona...\n";
    $st = new CoronaStatistic;
    $st->scrape();
    $now = strtotime(gmdate("Y-m-d H:i:s"));

    $file = CORONA_STATS_STORAGE."/indonesia_data.json";
    if (file_exists($file)) {
        $jsonOldData = file_get_contents($file);
    } else {
        $jsonOldData = "[]";
    }

    $data = $st->getNewCases("Indonesia");

    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonData !== $jsonOldData) {
        file_put_contents($file, $jsonData);

        $oldData = json_decode($jsonOldData, true);
        if (!is_array($oldData)) {
            $oldData = [];
        }
        $data = array_diff($data, $oldData);

        $text = "[Coronavirus Update (for Indonesia)]\n";
        foreach ($data as $k => $v) {
            $text .= "{$k}. ({$v["date"]}) {$v["amount"]} new {$v["type"]} {$v["source"]}\n\n";
        }

        foreach ($listChatId as $v) {
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
    } else {
        echo "No changes!\n";
    }

    sleep(60);
}
