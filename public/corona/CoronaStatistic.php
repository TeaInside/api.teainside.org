<?php

namespace CoronaStatistic;

if (!defined("CORONA_STATS_STORAGE")) {
  exit("CORONA_STATS_STORAGE is not defined!\n");
}

use CoronaStatistic\Scrapers\Worldometers;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @version 0.0.1
 */
final class CoronaStatistic
{
  /**
   * @var array
   */
  private $storageRealPath;

  /**
   * Constructor.
   */
  public function __construct()
  {
    // Create storage directories.
    is_dir(CORONA_STATS_STORAGE) or mkdir(CORONA_STATS_STORAGE);
    if (!is_dir(CORONA_STATS_STORAGE)) {
      exit("Cannot create CORONA_STATS_STORAGE at: ".CORONA_STATS_STORAGE);
    }

    $this->storageRealPath = realpath(CORONA_STATS_STORAGE);

    require_once __DIR__."/BaseScraper.php";
  }

  /**
   * @return \CoronaStatistic\Scrapers\Worldometers
   */
  public function worldometers(): Worldometers
  {
    require_once __DIR__."/Scrapers/Worldometers.php";
    return new Worldometers($this);
  }

  /**
   * @return string
   */
  public function getStorageRealPath(): string
  {
    return $this->storageRealPath;
  }
}
