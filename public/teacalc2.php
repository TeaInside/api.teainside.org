<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (
	isset($_GET["key"], $_GET["expr"]) &&
	($_GET["key"] === "8e7eaa2822cf3bf77a03d63d2fbdeb36df0a409f") &&
	is_string($_GET["expr"])
) {
	$a = new TeaCalc;
	echo $a->exec($_GET["expr"]);	
} else {
	echo json_encode(["error" => "Invalid request"]);
}

class TeaCalc
{
	private $token;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @param string $expr
	 */
	public function exec(string $expression)
	{
		$expression = trim($expression);
		$hash = sha1($expression);
		is_dir(__DIR__."/cache/") or mkdir(__DIR__."/cache/");
		$cacheFile = __DIR__."/cache/".$hash;

		$this->resolveToken();

		if (file_exists($cacheFile)) {
			$o["out"] = file_get_contents($cacheFile);
			goto ret;
		}

		$expression = urlencode($expression);
		$o = $this->curl("https://www.symbolab.com/pub_api/steps?userId=fe&query={$expression}&language=en&subscribed=false&plotRequest=PlotOptional");

		$ret = json_decode($o["out"], true);

		if (isset($ret["solutions"])) {
			file_put_contents($cacheFile, $o["out"]);
		}

		ret:
		return $o["out"];
	}

	/**
	 * @return void
	 */
	public function resolveToken(): void
	{
		if (file_exists(__DIR__."/token.json")) {
			$token = json_decode(file_get_contents(__DIR__."/token.json"), true);
			if (isset($token["token"], $token["expired_at"]) && ($token["expired_at"] > time())) {
				$this->token = $token["token"];
				return;
			}
		}

		$ret = null;
		$this->curl(
			"https://www.symbolab.com/solver/limit-calculator/%5Clim_%7Bx%5Cto%5Cinfty%7D%5Cleft(x%5E%7B2%7D%5Cright)",
			[
				CURLOPT_CUSTOMREQUEST => "HEAD",
				CURLOPT_HTTPHEADER => [],
				CURLOPT_USERAGENT => "curl",
				CURLOPT_HEADER => true,
				CURLOPT_WRITEFUNCTION => function ($ch, $str) use (&$ret) {
					if (preg_match("/sy2\.pub\.token=(.+?);/", $str, $m)) {
						file_put_contents(
							__DIR__."/token.json",
							json_encode(
								$ret = [
									"token" => $m[1],
									"expired_at" => (time() + 7200)
								]
							)
						);
						return 0;
					}
					return strlen($str);
				}
			]
		);

		if (isset($ret["token"])) {
			$this->token = $ret["token"];
		}
	}

	/**
	 * @return array
	 */
	function buildHeader(): array
	{
		return [
			"X-Requested-With: XMLHttpRequest",
			"Authorization: Bearer ".($this->token)
		];
	}

	/**
	 * @param string $url
	 * @param array  $opt
	 * @return array
	 */
	function curl(string $url, array $opt = []): array
	{
		$ch = curl_init($url);
		$optf = [
			CURLOPT_HTTP_VERSION => 2,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 60,
			CURLOPT_TIMEOUT => 60
		];
		foreach ($opt as $k => $v) {
			$optf[$k] = $v;
		}
		if (!isset($optf[CURLOPT_HTTPHEADER])) {
			$optf[CURLOPT_HTTPHEADER] = $this->buildHeader();
		}
		curl_setopt_array($ch, $optf);
		$o = curl_exec($ch);
		$err = curl_error($ch);
		$ern = curl_errno($ch);
		return [
			"out" => $o,
			"err" => $err,
			"ern" => $ern
		];
	}
}