<?php

namespace TeaMath;

defined("TEAMATH_STORAGE") or exit("TEAMATH_STORAGE is not defined");
is_string(TEAMATH_STORAGE) or exit("TEAMATH_STORAGE must be a string");

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @package \TeaMath
 * @version 0.0.1
 */
final class TeaMath
{
  /**
   * @const string
   */
  private const TOKEN_RESOLVER_URL = "https://www.symbolab.com/solver/step-by-step/";

  /**
   * @const array
   */
  private const USER_AGENT_LIST = [
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
   * @var string
   */
  private $token;

  /**
   * @var string
   */
  private $tokenFile;

  /**
   * @var string
   */
  private $cookieFile;

  /**
   * @var string
   */
  private $uDir;

  /**
   * @var string
   */
  private $cacheDir;

  /**
   * @param string $uniqueId
   * @throws \TeaMath\TeaMathException
   *
   * Constructor.
   */
  public function __construct(string $uniqueId = "_")
  {
    is_dir(TEAMATH_STORAGE) or mkdir(TEAMATH_STORAGE);
    if (!is_dir(TEAMATH_STORAGE)) {
      throw new TeaMathException("Cannot create TEAMATH_STORAGE directory: ".TEAMATH_STORAGE);
    }
    if (!is_readable(TEAMATH_STORAGE)) {
      throw new TeaMathException("TEAMATH_STORAGE directory is not readable: ".TEAMATH_STORAGE); 
    }
    if (!is_writeable(TEAMATH_STORAGE)) {
      throw new TeaMathException("TEAMATH_STORAGE directory is not writeable: ".TEAMATH_STORAGE); 
    }

    $this->uDir = TEAMATH_STORAGE."/user/".($uniqueId ? $uniqueId : "_");
    is_dir(TEAMATH_STORAGE."/user") or mkdir(TEAMATH_STORAGE."/user");
    is_dir($this->uDir) or mkdir($this->uDir);
    if (!is_dir($this->uDir)) {
      throw new TeaMathException("Cannot create uDir directory: ".$this->uDir);
    }
    if (!is_readable($this->uDir)) {
      throw new TeaMathException("uDir directory is not readable: ".$this->uDir);
    }
    if (!is_writeable($this->uDir)) {
      throw new TeaMathException("uDir directory is not writeable: ".$this->uDir);
    }

    $this->cacheDir = TEAMATH_STORAGE."/cache";
    is_dir($this->cacheDir) or mkdir($this->cacheDir);
    if (!is_dir($this->cacheDir)) {
      throw new TeaMathException("Cannot create cacheDir directory: ".$this->cacheDir);
    }
    if (!is_readable($this->cacheDir)) {
      throw new TeaMathException("cacheDir directory is not readable: ".$this->cacheDir);
    }
    if (!is_writeable($this->cacheDir)) {
      throw new TeaMathException("cacheDir directory is not writeable: ".$this->cacheDir);
    }

    $this->tokenFile = $this->uDir."/token.json";
    $this->cookieFile = $this->uDir."/cookie.txt";
  }

  /**
   * @throws \TeaMath\TeaMathException
   * @return bool
   */
  public function initToken(): bool
  {
    $tokenResolved = false;
    $needOnlineResolve = true;

    if (file_exists($this->tokenFile)) {
      $tkn = json_decode(file_get_contents($this->tokenFile), true);
      if (isset($tkn["expired"], $tkn["token"]) &&
        ($tkn["expired"] > gmmktime()) && is_string($tkn["token"])
      ) {
        $this->token = $tkn["token"];
        $tokenResolved = true;
        $needOnlineResolve = false;
      }
    }

    if ($needOnlineResolve) {
      file_exists($this->cookieFile) and unlink($this->cookieFile);
      $this->curl(self::TOKEN_RESOLVER_URL);
      $cookieContent = file_get_contents($this->cookieFile);
      if (preg_match("/sy2\.pub\.token\\s+(.+)\\n/", $cookieContent, $m)) {
        $m[1] = trim($m[1]);
        $this->token = $m[1];
        $tkn = [
          "token" => $m[1],
          "expired" => (gmmktime()+1000)
        ];
        file_put_contents($this->tokenFile,
          json_encode($tkn, JSON_PRETTY_PRINT), LOCK_EX);
        $tokenResolved = true;
      }
    }

    return $tokenResolved;
  }

  /**
   * @param string $q
   * @return ?array
   */
  public function query(string $q): ?array
  {
    $q = trim($q);
    $hash = sha1($q);
    $needOnlineQuery = true;
    $cacheFile = $this->cacheDir."/".$hash.".json.gz";

    if (file_exists($cacheFile)) {
      $arr = json_decode(gzdecode(file_get_contents($cacheFile)), true);
      if (
        isset($arr["expired"], $arr["data"]) && is_int($arr["expired"]) &&
        is_array($arr["data"]) && ($arr["expired"] > gmmktime())
      ) {
        $needOnlineQuery = false;
      }
    }

    if ($needOnlineQuery) {
      $o = $this->curl(
        "https://www.symbolab.com/pub_api/steps?userId=fe&query=".urlencode($q)."&language=en&subscribed=false&plotRequest=PlotOptional",
        [
          CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$this->token}",
            "X-Requested-With: XMLHttpRequest"
          ]
        ]
      );

      $arr = [
        "expired" => (gmmktime() + (3600 * 24 * 7)),
        "data" => json_decode($o["out"], true)
      ];

      file_put_contents($cacheFile,
        gzencode(json_encode($arr, JSON_UNESCAPED_SLASHES), 9));
      return $arr["data"];
    }

    return $arr["data"];
  }

  /**
   * @param string $url
   * @param array  $opt
   * @throws \TeaMath\TeaMathException
   */
  private function curl(string $url, array $opt = []): array
  {
    $retried = false;
    start_curl:
    $ch = curl_init($url);
    $optf = [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERAGENT => self::USER_AGENT_LIST[array_rand(self::USER_AGENT_LIST)],
      CURLOPT_ENCODING => "gzip",
      CURLOPT_COOKIEJAR => $this->cookieFile,
      CURLOPT_COOKIEFILE => $this->cookieFile,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_CONNECTTIMEOUT => 60
    ];
    foreach ($opt as $k => $v) {
      $optf[$k] = $v;
    }
    curl_setopt_array($ch, $optf);
    $r = [
      "out" => curl_exec($ch),
      "info" => curl_getinfo($ch)
    ];
    $err = curl_error($ch);
    $ern = curl_errno($ch);
    curl_close($ch);

    if ($err) {
      if (!$retried) {
        $retried = true;
        goto start_curl;
      }
      throw new TeaMathException("Curl Error: ({$ern}) {$err}");
    }
    return $r;
  }
}
