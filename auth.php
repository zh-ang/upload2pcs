<?php

require(__DIR__."/config.inc.php");

$auth_file = __DIR__ . "/token.json";

/* {{{ function _oauth_token(array $params, $auth_file)  */
function _oauth_token(array $params, $auth_file) {

    $verify_url = "https://openapi.baidu.com/oauth/2.0/token" . "?" . http_build_query($params);

    $json = file_get_contents($verify_url);

    $arrRes = json_decode($json, true);

    if (isset($arrRes["access_token"])) {
        file_put_contents($auth_file, $json);
        return true;
    } else {
        var_dump($json);
        return false;
    }

}
/* }}} */

if (isset($_GET["code"])) {

    $params = array(
        "grant_type" => "authorization_code",
        "code" => $_GET["code"],
        "client_id" => BAIDU_APPKEY,
        "client_secret" => BAIDU_SECRET,
        "redirect_uri" => "oob",
    );

    switch (_oauth_token($params, $auth_file)) {
        case true:
            echo "Authorize succeed!\n";
            break;
        case false:
            echo "Authorize failed!\n";
            break;
    }

    echo <<<HTML
<a href="{$_SERVER["SCRIPT_NAME"]}">Back</a>
HTML;
    exit();

}

$params = array(
    "response_type" => "code",
    "client_id" => BAIDU_APPKEY,
    "redirect_uri" => "oob",
    "scope" => "netdisk",
    "display" => "page",
);
$login_url = "https://openapi.baidu.com/oauth/2.0/authorize" . "?" . http_build_query($params);

?>

<form action="" method="get">
Authorization code (<a href="<?=$login_url?>" target="_blank">Login</a>):
<input name="code" type="text" autocomplete="off" />
<input type="submit" value="Grant" />
</form>

<?php

if (file_exists($auth_file)) {

    echo <<<HTML
<hr />
<a href="{$_SERVER["SCRIPT_NAME"]}">re-check</a>

HTML;
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
    $url = "https://openapi.baidu.com/rest/2.0/passport/users/getLoggedInUser?access_token={$token}";

    $info = (array) json_decode(file_get_contents($url), true);

    if ($info && isset($info["uname"])) {
        printf("access_token is ok! [%s]\n", $info["uname"]);
        exit();
    }

    var_dump($info);

    // try refresh
    $params = array(
        "grant_type" => "refresh_token",
        "refresh_token" => $arrAuth["refresh_token"],
        "client_id" => BAIDU_APPKEY,
        "client_secret" => BAIDU_SECRET,
        "redirect_uri" => "oob",
    );

    switch (_oauth_token($params, $auth_file)) {
        case true:
            echo "Refresh succeed!\n";
            break;
        case false:
            echo "Refresh failed!\n";
            unlink($auth_file);
            break;
    }


}
