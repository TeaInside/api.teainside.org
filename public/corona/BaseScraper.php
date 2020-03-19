<?php

namespace CoronaStatistic;

if (!defined("CORONA_STATS_STORAGE")) {
  exit("CORONA_STATS_STORAGE is not defined!\n");
}

use CoronaStatistic\CoronaStatistic;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 */
abstract class BaseScraper
{
  /**
   * @var \CoronaStatistic\CoronaStatistic
   */
  protected $coronaStat;

  /**
   * @var array
   */
  protected $errors = [];

  /**
   * @param \CoronaStatistic\CoronaStatistic $coronaStat
   *
   * Constructor.
   */
  public function __construct(CoronaStatistic $coronaStat)
  {
    $this->coronaStat = $coronaStat;
  }

  const USER_AGENT_LIST = [
    "Mozilla/5.0 (Windows NT 5.1; rv:7.0.1) Gecko/20100101 Firefox/7.0.1",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0",
    "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0",
    "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.157 Safari/537.36",
    "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36",
    "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.83 Safari/537.1",
    "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36"
  ];

  /**
   * @param string $url
   * @param array  $opt
   * @return array
   */
  public static function curl(string $url, array $opt = []): array
  {
    $retried = false;

    start_curl:
    $ch = curl_init($url);
    $optf = [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERAGENT => self::USER_AGENT_LIST[array_rand(self::USER_AGENT_LIST)],
    ];
    foreach ($opt as $k => $v) {
      $optf[$k] = $v;
    }
    unset($opt);
    curl_setopt_array($ch, $optf);
    $r = [
      "out" => curl_exec($ch),
      "err" => curl_error($ch),
      "ern" => curl_errno($ch),
      "info" => curl_getinfo($ch)
    ];
    curl_close($ch);

    if ($r["err"]) {
      if (!$retried) {
        $retried = true;
        goto start_curl;
      }
    }

    return $r;
  }

  /**
   * @param mixed $country
   * @return ?array
   */
  abstract public function getCountryData($country): ?array;

  /**
   * @return ?array
   */
  abstract public function getAllCountriesData(): ?array;

  /**
   * @return ?array
   */
  abstract public function getGlobalData(): ?array;
}
