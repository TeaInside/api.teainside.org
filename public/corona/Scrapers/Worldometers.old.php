<?php

namespace CoronaStatistic\Scrapers;

if (!defined("CORONA_STATS_STORAGE")) {
  exit("CORONA_STATS_STORAGE is not defined!\n");
}

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

    if (file_exists($this->lastScrapeFile)) {
      $this->lastScrape = (int)file_get_contents($this->lastScrapeFile);
    }
  }

  /**
   * @return bool
   */
  public function scrape(): bool
  {
    $o = static::curl("https://www.worldometers.info/coronavirus/");
    if ($o["err"]) {
      return false;
    }
    $this->o = $o["out"];
    if ($this->processGlobalData($this->o)) {
      file_put_contents($this->oFile, $this->o);
      file_put_contents($this->lastScrapeFile, time());
    }
    // $this->processCountriesData($this->o);
    return true;
  }

  /**
   * @param string $o
   * @return array
   */
  private function processGlobalData(string $o): ?array
  {
    $cmt = $fst = $sdt = 0;

    // Get cmt
    $c = explode("<h1>Coronavirus Cases:</h1>\n    <div class=\"maincounter-number\">", $o, 2);
    var_dump($c);die;
    if (isset($c[1])) {
      $c = explode("<", $c[1], 2);
      $cmt = (int)str_replace(",", "", strip_tags($c[0]));
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

    if (!$err) {
      var_dump($this->errors);
      return null;
    }

    $this->lastScrape = gmmktime();

    $this->globalData = [
      "scope" => "country:{$countryName}",
      "cmt" => $cmt,
      "fst" => $fst,
      "sdt" => $sdt,
      "scraped_at" => $this->lastScrape
    ];
    file_put_contents(
      $this->globalDataFile,
      json_encode($this->globalData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
      LOCK_EX
    );

    return $this->globalData;
  }

  /**
   * @return ?array
   */
  public function getGlobalData(): ?array
  {
    $doScrape = true;
    if (!is_array($this->globalData)) {
      if (file_exists($this->globalDataFile) && ($this->lastScrape + 600) > gmmktime()) {
        $this->globalData = json_decode(file_get_contents($this->globalDataFile), true);
        if (is_array($this->globalData)) {
          $doScrape = false;
        }
      }
    }

    if ($doScrape) {
      $this->scrape();
    }

    return $this->globalData;
  }

  /**
   * @param mixed $country
   * @return ?array
   */
  public function getCountryData($country): ?array
  {
    $doScrape = true;
    if (!is_array($this->globalData)) {
      if (file_exists($this->globalDataFile) && ($this->lastScrape + 600) > gmmktime()) {
        $this->globalData = json_decode(file_get_contents($this->globalDataFile), true);
        if (is_array($this->globalData)) {
          $doScrape = false;
        }
      }
    }

    if ($doScrape) {
      $this->scrape();
    }

    $tm = 0;
    $cmt = $fst = $sdt = 0;
    // $c = explode("<tr style=\"\"> <td style=\"font-weight: bold; font-size:15px; text-align:left; padding-left:3px;\"> {$countryName} </td>", $this->o, 2);

    $countryName = preg_quote(strtolower($countryName));
    $c = explode("<table id=\"main_table_countries\" ", $this->o, 2);
    //var_dump($c);
    die;
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

    return [];
  }

  /**
   * @return ?array
   */
  public function getAllCountriesData(): ?array
  {
  }
}
