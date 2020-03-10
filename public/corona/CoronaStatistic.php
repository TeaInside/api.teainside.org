<?php

if (!defined("CORONA_STATS_STORAGE")) {
    exit("CORONA_STATS_STORAGE is not defined!\n");
}

class CoronaStatistic
{
    /**
     * @var string
     */
    private $errors = [];

    /**
     * @var array
     */
    private $globalData = [];

    /**
     * @var string
     */
    private $o = "";

    /**
     * Constructor.
     */
    public function __construct()
    {
        is_dir(CORONA_STATS_STORAGE) or mkdir(CORONA_STATS_STORAGE);
        if (file_exists(CORONA_STATS_STORAGE."/global.json")) {
            $this->globalData = json_decode(
                file_get_contents(CORONA_STATS_STORAGE."/global.json"),
                true
            );
        }
        if (file_exists(CORONA_STATS_STORAGE."/last_scrape.html")) {
            $this->o = file_get_contents(CORONA_STATS_STORAGE."/last_scrape.html");
        }
    }

    /**
     * @return bool
     */
    public function scrape(): bool
    {
        $err = false;
        // $o = file_get_contents(__DIR__."/dummy.html");

        $ch = curl_init("https://www.worldometers.info/coronavirus/");
        curl_setopt_array($ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        );
        $o = curl_exec($ch);

        $cmt = $fst = $sdt = 0;

        // Get cmt
        $c = explode("<strong>Total:</strong></td> <td>", $o, 2);
        if (isset($c[1])) {
            $c = explode("<", $c[1], 2);
            $cmt = (int)str_replace(",", "", $c[0]);
        } else {
            $this->errors[] = "Could not get cmt";
            $err = true;
        }

        // Get fst
        $c = explode("<h1>Deaths:</h1> <div class=\"maincounter-number\"> <span>", $o, 2);
        if (isset($c[1])) {
            $c = explode("<", $c[1], 2);
            $fst = (int)str_replace(",", "", $c[0]);
        } else {
            $this->errors[] = "Could not get fst";
            $err = true;
        }

        // Get sdt
        $c = explode("<h1>Recovered:</h1> <div class=\"maincounter-number\" style=\"color:#8ACA2B \"> <span>", $o, 2);
        if (isset($c[1])) {
            $c = explode("<", $c[1], 2);
            $sdt = (int)str_replace(",", "", $c[0]);
        } else {
            $this->errors[] = "Could not get sdt";
            $err = true;
        }

        // Save the result.
        $tm = strtotime(gmdate("Y-m-d H:i:s"));
        if (!$err) {
            file_put_contents(CORONA_STATS_STORAGE."/global.json",
                json_encode(
                    $this->globalData = [
                        "scope" => "global",
                        "cmt" => $cmt,
                        "fst" => $fst,
                        "sdt" => $sdt,
                        "scraped_at" => $tm
                    ]
                ));
            file_put_contents(CORONA_STATS_STORAGE."/last_scrape.html", $o);
        }

        file_put_contents(CORONA_STATS_STORAGE."/last_scrape.txt", $tm);
        $this->o = $o;

        return $err;
    }

    /**
     * @return array
     */
    public function getGlobal(): array
    {
        return $this->globalData;
    }

    /**
     * @param string $countryName
     * @return array
     */
    public function getCountry(string $countryName): array
    {
        if ($this->o === "") {
            $this->scrape();
        }

        $tm = 0;
        $cmt = $fst = $sdt = 0;
        // $c = explode("<tr style=\"\"> <td style=\"font-weight: bold; font-size:15px; text-align:left; padding-left:3px;\"> {$countryName} </td>", $this->o, 2);

        $countryName = preg_quote(strtolower($countryName));
        $c = explode("<table id=\"main_table_countries\" ", $this->o, 2);
        if (isset($c[1])) {
            $c = explode("</table>", $c[1], 2);
            $c = explode("<tr style=\"\">", $c[0]);
            foreach ($c as $k => $v) {
                $v = strtolower($v);
                if (preg_match("/\W{$countryName}\W/", $v)) {
                    if (preg_match_all("/<td[^\<\>]+>(.*)<\/td>/Usi", $v, $m)) {
                        $m = $m[1];
                        $cmt = (int)str_replace(",", "", $m[1]);
                        $fst = (int)str_replace(",", "", $m[4]);
                        $sdt = (int)str_replace(",", "", $m[6]);
                        $tm = strtotime(gmdate("Y-m-d H:i:s"));
                        break;
                    }
                }
            }
        }

        return [
            "scope" => "country:{$countryName}",
            "cmt" => $cmt,
            "fst" => $fst,
            "sdt" => $sdt,
            "scraped_at" => $this->globalData["scraped_at"]
        ];
    }

    /**
     * @param string $countryName
     * @return array
     */
    public function getAllCountry(): array
    {
        if ($this->o === "") {
            $this->scrape();
        }
        // $c = explode("<tr style=\"\"> <td style=\"font-weight: bold; font-size:15px; text-align:left; padding-left:3px;\"> {$countryName} </td>", $this->o, 2);

        $data = [
            "scraped_at" => isset($this->globalData["scraped_at"]) ?
                $this->globalData["scraped_at"] :
                strtotime(gmdate("Y-m-d H:i:s")),
            "data" => []
        ];

        $countryName = preg_quote(strtolower($countryName));
        $c = explode("<table id=\"main_table_countries\" ", $this->o, 2);
        if (isset($c[1])) {
            $c = explode("</table>", $c[1], 2);
            $c = explode("<tr style=\"\">", $c[0]);
            foreach ($c as $k => $v) {
                if (preg_match_all("/<td[^\<\>]+>(.*)<\/td>/Usi", $v, $m)) {
                    $m = $m[1];
                    $data["data"][] = [
                        "country" => trim(strip_tags($m[0])),
                        "cmt" => (int)str_replace(",", "", $m[1]),
                        "fst" => (int)str_replace(",", "", $m[4]),
                        "sdt" => (int)str_replace(",", "", $m[6]),
                    ];
                }
            }
        }

        return $data;
    }
}
