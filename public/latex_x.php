<?php

require __DIR__."/../third_party/tex2png/autoload.php";

use Gregwar\Tex2png\Tex2png;


if (isset($_GET["exp"]) && is_string($_GET["exp"])) {
	if (strpos($_GET["exp"], "\\int") !== false) {
		$_GET["exp"] = str_replace("\\int ", "\\int\\(pure_space)", $_GET["exp"]);
		$_GET["exp"] = implode("\\;d", explode("d", $_GET["exp"]));
	}
	$_GET["exp"] = str_replace(" ", "\\,", trim($_GET["exp"]));
	$_GET["exp"] = str_replace("\\(pure_space)", " ", $_GET["exp"]);

	$hash = sha1($_GET["exp"]);
	$st = new Tex2png($_GET["exp"]);
	if (file_exists(__DIR__."/latex/{$hash}.png")) {
		$st->error = null;
	} else {
		$st->saveTo(__DIR__."/latex/{$hash}.png")->generate();
	}

	if ($st->error) {
		header("Content-Type: application/json");
		print json_encode(["error" => $st->error->__toString()], JSON_UNESCAPED_SLASHES);
	} else {
		header("Content-Type: image/png");
		readfile(__DIR__."/latex/{$hash}.png");
	}
	exit;
}

print "no_response";