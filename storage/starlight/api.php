<?php

if (isset($_GET["domain"], $_GET["sb_act"]) && is_string($_GET["domain"]) && is_string($_GET["sb_act"])) {

	if ($_GET["domain"] !== "starlight.teainside.org") {
		$subErrMsg = "Invalid domain ".$_GET["domain"];
		goto err;
	}

	if ($_GET["sb_act"] === "set" && ($_SERVER["REQUEST_METHOD"] === "POST")) {
		if (isset($_GET["set_target"]) && is_string($_GET["set_target"])) {

			$ipton = hexdec($_GET["set_target"]);
			$target_ip = sprintf(
				"%d.%d.%d.%d",
				(($ipton & (0xff << 24)) >> 24),
				(($ipton & (0xff << 16)) >> 16),
				(($ipton & (0xff << 8)) >> 8),
				($ipton & 0xff)
			);

			if (!filter_var($target_ip, FILTER_VALIDATE_IP)) {
				$subErrMsg = "Invalid tea_ipton_resolve";
				goto err;
			}

			file_put_contents(__DIR__."/prox_target.json", json_encode(
				[
					"target" => $target_ip
				],
				JSON_PRETTY_PRINT
			));

			// shell_exec("/usr/sbin/service nginx reload");

			print json_encode(
				[
					"status" => "ok",
					"message" => "Domain starlight.teainside.org has been set to ".$target_ip
				]
			);
			exit;
		}
	} else if ($_GET["sb_act"] === "get" && ($_SERVER["REQUEST_METHOD"] === "GET")) {
		print(file_get_contents(__DIR__."/prox_target.json"));
		exit;
	} else {
		$subErrMsg = "Invalid sb_act.";
		goto err;
	}
}

err:
$code = 400;
