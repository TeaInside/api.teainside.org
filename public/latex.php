<?php

require __DIR__."/../third_party/tex2png/autoload.php";

use Gregwar\Tex2png\Tex2png;

if (isset($_GET["exp"]) && is_string($_GET["exp"])) {
	header("Content-Type: application/json");
	$hash = sha1($_GET["exp"]);
	Tex2png::create($_GET["exp"])
	    ->saveTo(__DIR__."/latex/{$hash}.png", "600x600")
	    ->generate();

	print json_encode(
		[
			"ret" => "https://api.teainside.org/latex/{$hash}.png"
		]
	);
	exit;
}

print "no_response";