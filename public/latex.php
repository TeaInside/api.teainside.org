<?php

require __DIR__."/../third_party/tex2png/autoload.php";

use Gregwar\Tex2png\Tex2png;

if (isset($_GET["exp"]) && is_string($_GET["exp"])) {
	header("Content-Type: application/json");
	$hash = sha1($_GET["exp"]);
	$st = Tex2png::create($_GET["exp"]);
	$st->saveTo(__DIR__."/latex/{$hash}.png")
	    ->generate();

	if ($st->error) {
		print json_encode(
			[
				"error" => $st->error
			]
		)
	} else {
		print json_encode(
			[
				"ret" => "https://api.teainside.org/latex/{$hash}.png"
			]
		);
	}
	exit;
}

print "no_response";