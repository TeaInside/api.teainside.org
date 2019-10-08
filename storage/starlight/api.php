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

			$nginx_conf = str_replace("{{target_ip}}", $target_ip, nginx_conf());
			if (file_put_contents("/root/server/nginx/sites-available/@@.teainside.org/starlight.teainside.org", $nginx_conf)) {
				shell_exec("/usr/sbin/service nginx restart");
				print json_encode(
					[
						"status" => "ok",
						"message" => "Domain starlight.teainside.org has been set to ".$target_ip
					]
				);
				exit;
			}
			$subErrMsg = "Cannot rewrite nginx conf, contact admin!";
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


function nginx_conf()
{
	return <<<'EOF'

server {
	listen 80;
	listen [::]:80;
	
	server_name starlight.teainside.org;

	access_log /var/www/starlight.teainside.org/logs/access.log cfssl;
	error_log /var/www/starlight.teainside.org/logs/error.log crit;

	return 301 https://starlight.teainside.org$request_uri;
}

server {
	listen 443 ssl;
	listen [::]:443 ssl;

	ssl_certificate /var/www/.0_ssl/@@.teainside.org/crtca;
	ssl_certificate_key /var/www/.0_ssl/@@.teainside.org/key;

	server_name  starlight.teainside.org www.pb-starlight.com pb-starlight.com;

	access_log /var/www/starlight.teainside.org/logs/access.log cfssl;
	error_log /var/www/starlight.teainside.org/logs/error.log crit;

	location / {
		proxy_pass http://{{target_ip}};
		proxy_set_header "X-Real-Origin-Host" $host;
		proxy_set_header "X-Tea-Inside-Server" $host;
		proxy_set_header "Host" "starlight.teainside.org";
	}
}

server {
	listen 80;
	listen [::]:80;

	server_name www.pb-starlight.com pb-starlight.com;

	access_log /var/www/starlight.teainside.org/logs/access.log cfssl;
	error_log /var/www/starlight.teainside.org/logs/error.log crit;

	location / {
		proxy_pass http://{{target_ip}};
		proxy_set_header "X-Real-Origin-Host" $host;
		proxy_set_header "X-Tea-Inside-Server" $host;
		proxy_set_header "Host" "starlight.teainside.org";
	}
}

EOF;
}