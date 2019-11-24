<?php

$token = null;

header("Content-Type: application/json");
print json_encode(calcExec("1+1+2"));


/**
 * @param string $expression
 * @return ?array
 */
function calcExec(string $expression): ?array
{
	global $token;
	$ret = null;

	$expression = trim($expression);
	$hash = sha1($expression);
	is_dir(__DIR__."/cache/") or mkdir(__DIR__."/cache/");
	$cacheFile = __DIR__."/cache/".$hash;

	if (file_exists(__DIR__."/token.json")) {
		$token = json_decode(file_get_contents(__DIR__."/token.json"), true);
		if (isset($token["token"], $token["expired_at"]) && ($token["expired_at"] > time())) {
			$token = $token["token"];
		}
	} else {
		curl(
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
			$token = $ret["token"];
		}
	}

	if (file_exists($cacheFile)) {
		$ret = json_decode(file_get_contents($cacheFile), true);
		goto ret;
	}

	$expression = urlencode($expression);
	$o = curl("https://www.symbolab.com/pub_api/steps?userId=fe&query={$expression}&language=en&subscribed=false&plotRequest=PlotOptional");

	$res = json_decode($o["out"], true);
	if (isset($res["solutions"])) {
		$ret = $res;
		file_put_contents($cacheFile, $o["out"]);
	} else {
		$ret = $res;
	}
	ret:
	return $ret;
}


/**
 * @return array
 */
function buildHeader(): array
{
	global $token;
	return [
		"X-Requested-With: XMLHttpRequest",
		"Authorization: Bearer ".($token)
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
		$optf[CURLOPT_HTTPHEADER] = buildHeader();
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