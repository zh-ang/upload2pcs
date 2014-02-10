<?php

if ($argc < 2) {
    die("Usage: {$argv[0]} <filename> [<target_name>]\n");
}

if (!file_exists($argv[1]) || !is_file($argv[1]) || !is_readable($argv[1])) {
    die("file not found: $argv[1]\n");
}

$filename = realpath($argv[1]);

$target = isset($argv[2]) ? $argv[2] : basename($filename);

require(__DIR__."/config.inc.php");

$auth_file = __DIR__ . "/token.json";

if (file_exists($auth_file)) {

    $json = file_get_contents($auth_file);
    /*
       {
       "access_token": "1.a6b7dbd428f731035f771b8d15063f61.86400.1292922000-2346678-124328",
       "expires_in": 86400,
       "refresh_token": "2.385d55f8615fdfd9edb7c4b5ebdc3e39.604800.1293440400-2346678-124328",
       "scope": "basic email",
       "session_key": "ANXxSNjwQDugf8615OnqeikRMu2bKaXCdlLxn",
       "session_secret": "248APxvxjCZ0VEC43EYrvxqaK4oZExMB",
       }
     */
    $arrAuth = (array) json_decode($json, true);

    $token = $arrAuth["access_token"];
    $url = "https://c.pcs.baidu.com/rest/2.0/pcs/file";
    $params = array(
        "method"    => "upload",
        "path"      => rtrim(PCS_PREFIX, "/") . "/{$target}",
        "access_token" => $token,
    );

    $url .= "?" . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => "@{$filename}"));
    curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    print "\n";

    if ($info["http_code"] == 200) {
        echo "OK!\n";
    } else {
        die("Upload failed!\n");
    }

}
