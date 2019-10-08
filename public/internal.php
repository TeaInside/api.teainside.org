<?php

header("Content-Type: application/json");

$code = 401;
$errMsg = "Unauthorized";

if (isset($_GET["action"], $_SERVER["HTTP_AUTHORIZATION"])) {

	if ($_GET["action"] === "nginx_prox") {

		// Starlight.
		if ($_SERVER["HTTP_AUTHORIZATION"] === "EcppB0QIiAaJ/fy6nQxhIvFPbAquTZ162mCbCLL3ooJ0THiQ+/T1xPYLsTLnRBVJfwAvDCVh9C2+") {
			require __DIR__."/../storage/starlight/api.php";
			$errMsg = "Bad Request".(isset($subErrMsg) ? ": ".$subErrMsg : "");
		}
	}
}

http_response_code($code);
print json_encode(
	[
		"error" => $code,
		"error_message" => $errMsg
	]
);
