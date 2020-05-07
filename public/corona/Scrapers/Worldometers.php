<?php

namespace CoronaStatistic\Scrapers;

use CoronaStatistic\BaseScraper;
use CoronaStatistic\CoronaStatistic;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 */
final class Worldometers extends BaseScraper
{
  /**
   * @var string
   */
  private
    $oFile,
    $o;

  /**
   * @var string
   */
  private $storagePath;

  /**
   * @var array
   */
  private
    $globalDataFile,
    $globalData;

  /**
   * @var array
   */
  private
    $countriesDataFile,
    $countriesData;

  /**
   * @var int
   */
  private
    $lastScrapeFile,
    $lastScrape = 0;

  /**
   * @param \CoronaStatistic\CoronaStatistic $coronaStat
   *
   * Constructor.
   */
  public function __construct(CoronaStatistic $coronaStat)
  {
    parent::__construct($coronaStat);
    $this->storagePath = $coronaStat->getStorageRealPath()."/worldometers";
    is_dir($this->storagePath) or mkdir($this->storagePath);

    if (!is_dir($this->storagePath)) {
      exit("Cannot create worldometers directory at: ".$this->storagePath);
    }

    $this->globalDataFile = $this->storagePath."/global_data.json";
    $this->countriesDataFile = $this->storagePath."/countries_data.json";
    $this->lastScrapeFile = $this->storagePath."/last_scrape.txt";
    $this->oFile = $this->storagePath."/o.html";
    $this->errorLogFile = $this->storagePath."/error_log.txt";

    if (file_exists($this->lastScrapeFile)) {
      $this->lastScrape = (int)file_get_contents($this->lastScrapeFile);
    }
  }

  /**
   * @param bool $force
   * @return bool
   */
  public function scrape(bool $force = false): bool
  {
    if ($force || (($this->lastScrape + 500) < gmmktime())) {
      $s = 0;
      $o = static::curl("https://www.worldometers.info/coronavirus/");
      if ($o["err"]) {
        return false;
      }
      $this->lastScrape = gmmktime();
      $this->o = $o["out"];
      unset($o);

      $jsonFlag = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

      $r = self::parseGlobalData($this->o);
      if (count($r["err"]) === 0) {
        unset($r["err"]);
        $this->globalData = $r;
        file_put_contents($this->globalDataFile, json_encode($r, $jsonFlag));
        $s++;
      }

      $r = self::parseCountriesData($this->o);
      if (count($r["err"]) === 0) {
        unset($r["err"]);
        $this->countriesData = $r["data"];
        file_put_contents($this->countriesDataFile, json_encode($r["data"], $jsonFlag));
        $s++;
      }

      file_put_contents($this->oFile, $this->o);
      file_put_contents($this->lastScrapeFile, $this->lastScrape);
      if ($s === 2) {
        @unlink($this->oFile.".fix");
        @unlink($this->lastScrapeFile.".fix");
        copy($this->oFile, $this->oFile.".fix");
        copy($this->lastScrapeFile, $this->lastScrapeFile.".fix");
      }
    } else {
      $this->globalData = json_decode(file_get_contents($this->globalDataFile), true);
      $this->countriesData = json_decode(file_get_contents($this->countriesDataFile), true);
      if (!is_array($this->globalData)) {
        $this->globalData = [];
      }
      if (!is_array($this->countriesData)) {
        $this->countriesData = [];
      }
    }

    return true;
  }

  /**
   * @param string $str
   * @return array
   */
  private static function parseGlobalData(string $str): array
  {
    $err = [];
    $cmt = $fst = $sdt = 0;
    if (preg_match(
      '/<h1>Coronavirus Cases:<\/h1>[\n\s]+<div class=\"maincounter-number\">[\n\s]+<span[^\<\>]*>([\d\,]+)[^\d\,]/Usi',
      $str, $m)
    ) {
      $cmt = (int)str_replace(",", "", $m[1]);
    } else {
      $err[] = "Cannot get cmt";
    }

    if (preg_match(
      '/<h1>Deaths:<\/h1>[\n\s]+<div class=\"maincounter-number\".*>[\n\s]+<span[^\<\>]*>([\d\,]+)[^\d\,]/Usi',
      $str, $m)
    ) {
      $fst = (int)str_replace(",", "", $m[1]);
    } else {
      $err[] = "Cannot get fst";
    }

    if (preg_match(
      '/<h1>Recovered:<\/h1>[\n\s]+<div class=\"maincounter-number\".*>[\n\s]+<span[^\<\>]*>([\d\,]+)[^\d\,]/Usi',
      $str, $m)
    ) {
      $sdt = (int)str_replace(",", "", $m[1]);
    } else {
      $err[] = "Cannot get sdt";
    }

    return [
      "scope" => "global",
      "cmt" => $cmt,
      "fst" => $fst,
      "sdt" => $sdt,
      "err" => $err
    ];
  }

  /**
   * @param string $str
   * @return array
   */
  private static function parseCountriesData(string $str): array
  {
    $data = $err = [];

    $c = explode('<table id="main_table_countries_today"', $str, 2);
    if (count($c) > 0) {
      $c = explode('</tbody>', $c[1], 2);
    } else {
      $err[] = "Cannot get countries table (1)";
      goto ret;
    }

    $c = explode('<tbody>', $c[0], 2);
    if (count($c) > 0) {
      if (preg_match_all("/<tr.*>(.+)<\/tr>/Usi", $c[1], $m)) {
        foreach ($m[1] as $k => $v) {
          if (preg_match_all("/<td[^\<\>]*>(.*)<\/td>/Usi", $v, $m)) {
            $m = $m[1];
            $country = strtolower(trim(strip_tags($m[0])));
            $data[$country] = [
              "scope" => "country:{$country}",
              "cmt" => (int)str_replace(",", "", $m[1]),
              "fst" => (int)str_replace(",", "", $m[3]),
              "sdt" => (int)str_replace(",", "", $m[5]),
              "new_cmt" => (int)str_replace(",", "", $m[2]),
              "new_fst" => (int)str_replace(",", "", $m[4]),
              "active_cmt" => (int)str_replace(",", "", $m[6])
            ];
          }
        }
      } else {
        $err[] = "Cannot get countries table (3)";
      }
    } else {
      $err[] = "Cannot get countries table (2)";
      goto ret;
    }

    ret:
    return [
      "data" => $data,
      "err" => $err
    ];
  }

  /**
   * @return ?array
   */
  public function getGlobalData(): ?array
  {
    $this->scrape();
    return $this->globalData + ["scraped_at" => $this->lastScrape];
  }

  /**
   * @param mixed $country
   * @return ?array
   */
  public function getCountryData($country): ?array
  {
    $this->scrape();
    if (is_string($country)) {
      return $this->countriesData[strtolower(trim($country))] 
      + ["scraped_at" => $this->lastScrape]
      ?? ["data" => "not_found"];
    } else {
      $r = [];
      $i = 0;
      $limit = 100;
      foreach ($country as $v) {
        if ($i >= $limit) {
          break;
        }
        if (is_string($v)) {
          $r[] = $this->countriesData[strtolower(trim($v))]
            ?? ["data" => "{$v}:not_found"];
        } else {
          $r = ["data" => "invalid_param"];
          break;
        }
        $i++;
      }
      return ["vector" => $r, "scraped_at" => $this->lastScrape];
    }
  }

  /**
   * @return ?array
   */
  public function getAllCountriesData(): ?array
  {
    $this->scrape();
    return [
      "data" => array_values($this->countriesData),
      "scraped_at" => $this->lastScrape
    ];
  }
}
