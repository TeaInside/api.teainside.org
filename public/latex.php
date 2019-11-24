<?php

require __DIR__."/../third_party/tex2png/autoload.php";

use Gregwar\Tex2png\Tex2png;

if (isset($_GET["exp"]) && is_string($_GET["exp"])) {
	header("Content-Type: application/json");
	$hash = sha1($_GET["exp"]);
	$st = new Tex2png($_GET["exp"]);
	$st->saveTo(__DIR__."/latex/{$hash}.png")
	    ->generate();

	if ($st->error) {
		print json_encode([
				"error" => $st->error->__toString()
			],
			JSON_UNESCAPED_SLASHES
		);
	} else {
		print json_encode([
				"ret" => "https://api.teainside.org/latex/{$hash}.png"
			],
			JSON_UNESCAPED_SLASHES
		);
	}
	exit;
}

print "no_response";