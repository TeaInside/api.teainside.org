<?php

header("Content-Type: application/json");

$code = 200;
if (isset($_GET["key"]) && is_string($_GET["key"])) {
  if ($_GET["key"] !== "8e7eaa2822cf3bf77a03d63d2fbdeb36df0a409f") {
    $r = ["error" => "unauthorized"];
    $code = 401;
    goto res;
  }

  if (isset($_GET["q"]) && is_string($_GET["q"])) {
    define("TEAMATH_STORAGE", __DIR__."/storage");
    require __DIR__."/src/autoload.php";
    $teamath = new TeaMath\TeaMath(sha1("qwe"));
    $teamath->initToken();
    $r = $teamath->query($_GET["q"]);
  } else {
    $code = 400;
    $r = ["error" => "\"q\" parameter required!"];
  }
} else {
  $r = ["error" => "unauthorized"];
  $code = 401;
}

res:
http_response_code($code);
echo json_encode($r, JSON_UNESCAPED_SLASHES);
