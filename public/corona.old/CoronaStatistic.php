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
     * @var int
     */
    private $lastScrape;

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

        // Get old global data if exists.
        if (file_exists(CORONA_STATS_STORAGE."/global.json")) {
            $this->globalData = json_decode(
                file_get_contents(CORONA_STATS_STORAGE."/global.json"),
                true
            );
        }

        // Get old last scrape HTML page if exists.
        if (file_exists(CORONA_STATS_STORAGE."/last_scrape.html")) {
            $this->o = file_get_contents(CORONA_STATS_STORAGE."/last_scrape.html");
        }

        // Get last scrape time if exists.
    }

    /**
     * @return ?int
     */
    public function lastScrape(): ?int
    {
        return $this->lastScrape;
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
            file_put_contents(CORONA_STATS_STORAGE."/last_page.html", $o);
        }
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
                    $v = preg_replace("/<!--.+-->/US", "", $v);
                    if (preg_match_all("/<td[^\<\>]+>(.*)<\/td>/Usi", $v, $m)) {
                        $m = $m[1];
                        $cmt = (int)str_replace(",", "", $m[1]);
                        $fst = (int)str_replace(",", "", $m[3]);
                        $sdt = (int)str_replace(",", "", $m[5]);
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

    /**
     * @param string $country
     * @return array
     */
    public function getNewCases(string $country): array
    {
        $country = preg_quote($country);
        $data = [];

        $intPattern = 
            // "/(\d)\snew\s(\w+)\sin\s{$country}.+<a href=\"(.+)\".+>source<\/a>/USi",
            "/<li><strong>(.{10,50})\sin\s{$country}<\/strong>.+<a href=\"(.+)\".+>source<\/a>/USi";

        $closure = function ($mm) use (&$data, $intPattern) {
            foreach ($mm[3] as $kk => $vv) {
                if (preg_match_all($intPattern, $vv, $m)) {
                    foreach ($m[1] as $k => $v) {

                        if (preg_match_all("/(\d+)\snew\s(\w+)/", $m[1][$k], $mx)) {
                            $r = "";
                            foreach ($mx[1] as $ki => $vi) {
                                $r .= " {$vi} new {$mx[2][$ki]} and";
                            }
                            $r = trim(rtrim($r, "and"));
                        }

                        $rd = [
                            "date" => $mm[1][$kk]." ".$mm[2][$kk],
                            "info" => $r,
                            "source" => html_entity_decode(trim($m[2][$k]), ENT_QUOTES, "UTF-8"),
                        ];
                        $data[md5($rd["source"])] = $rd;
                    }
                }        
            }
        };


        if (preg_match_all(
            "/<h4>(\w+)\s(\d+)(?:\s\(GMT\))?:(?:<br>)?<\/h4>.*<ul>(.+)window\.adsbygoogle/Usi",
            $this->o, $mm)) {
            $closure($mm);
        }

        if (preg_match_all(
            "/<h4>(\w+)\s(\d+)(?:\s\(GMT\))?:(?:<br>)?<\/h4>.*<ul>(.+)<\/ul>/Usi",
            $this->o, $mm)) {
            $closure($mm);
        }

        return $data;
    }
}
