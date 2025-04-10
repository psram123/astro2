<?php

error_reporting(0);

$APP_CONFIGS['STRING_CID'] = "tomato";

//============================================================================//

$_SERVER['HTTP_HOST'] = strtok($_SERVER['HTTP_HOST'], ':'); 
$_SERVER['HTTP_HOST'] = str_ireplace('localhost', '127.0.0.1', $_SERVER['HTTP_HOST']);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") { 
    $streamenvproto = "https"; 
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") { 
    $streamenvproto = "https"; 
} else { 
    $streamenvproto = "http"; 
} 

$plhoth = ($_SERVER['SERVER_ADDR'] !== "127.0.0.1") ? $_SERVER['HTTP_HOST'] : getHostByName(php_uname('n'));

if (isset($_SERVER['HTTP_CF_VISITOR']) && !empty($_SERVER['HTTP_CF_VISITOR'])) { 
    $htcfs = @json_decode($_SERVER['HTTP_CF_VISITOR'], true); 
    if (isset($htcfs['scheme']) && !empty($htcfs['scheme'])) { 
        $streamenvproto = $htcfs['scheme']; 
    }
}

//============================================================================//

function getRequest($url, $header)
{
    $process = curl_init($url);
    curl_setopt($process, CURLOPT_HTTPHEADER, $header);
    curl_setopt($process, CURLOPT_HEADER, 0);
    curl_setopt($process, CURLOPT_TIMEOUT, 10);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
    $return = curl_exec($process);
    $effURL = curl_getinfo($process, CURLINFO_EFFECTIVE_URL);
    $httpcode = curl_getinfo($process, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($process, CURLINFO_CONTENT_TYPE);
    curl_close($process);
    return array("url" => $effURL, "code" => $httpcode, "content_type" => $contentType, "data" => $return);
}

function extractChnsIDS($payload)
{
    $output = "";
    if (stripos($payload, "/") !== false) {
        $ifn = explode("/", $payload);
        if (isset($ifn[0]) && !empty($ifn[0])) {
            $output = trim($ifn[0]);
        }
    }
    return $output;
}

function extractChnsPart($payload)
{
    $output = "";
    if (stripos($payload, "/") !== false) {
        $itn = explode("/", $payload);
        if (isset($itn[1])) {
            array_shift($itn);
            foreach ($itn as $zhn) {
                $output .= $zhn . "/";
            }
            $output = rtrim($output, "/");
        }
    }
    return $output;
}

function extractRelativeBase($link)
{
    if (stripos($link, "?") !== false) {
        $xink = explode("?", $link);
        if (isset($xink[0]) && !empty($xink[0])) {
            $link = trim($xink[0]);
        }
    }
    $link = str_replace(basename($link), "", $link);
    return $link;
}

//============================================================================//

function getChannels()
{
    $clists = array(); 
    $liveC = array();
    $ePath = "ps_uk_sky.json"; // Updated JSON file
    if (file_exists($ePath)) {
        $xGets = @file_get_contents($ePath);
        if (!empty($xGets)) {
            $cData = @json_decode($xGets, true);
            if (isset($cData[0]['id']) && !empty($cData[0]['id'])) {
                $clists = $cData;
            }
        }
    }
    foreach ($clists as $ctls) {
        if (!empty($ctls['id']) && !empty($ctls['name']) && !empty($ctls['manifest_url'])) {
            $liveC[] = $ctls;
        }
    }
    return $liveC;
}

function getChannelDetail($id)
{
    $output = array();
    foreach (getChannels() as $igc) {
        if (md5($id) == md5($igc['id'])) {
            $output = $igc;
        }
    }
    if (!empty($output)) {
        $output['extension'] = "";
        if (stripos($output['manifest_url'], ".mpd") !== false) {
            $output['extension'] = ".mpd";
        }
        if (stripos($output['manifest_url'], ".m3u8") !== false) {
            $output['extension'] = ".m3u8";
        }
    }
    return $output;
}

function getChannelDetail_FEnd($id)
{
    global $APP_CONFIGS;
    global $plhoth;
    global $streamenvproto;
    $output = array();
    foreach (getChannels() as $igc) {
        if ($id == md5($igc['id'] . $APP_CONFIGS['STRING_CID'])) {
            $output = $igc;
        }
    }
    if (!empty($output)) {
        $output['extension'] = "";
        if (stripos($output['manifest_url'], ".mpd") !== false) {
            $output['extension'] = ".mpd";
        }
        if (stripos($output['manifest_url'], ".m3u8") !== false) {
            $output['extension'] = ".m3u8";
        }

        $playUrlBase = $streamenvproto . "://" . $plhoth . str_replace(" ", "%20", str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']));
        $playurl = $playUrlBase . $output['id'] . "/index" . $output['extension'];
        $output['playurl'] = $playurl;
    }
    return $output;
}

?>
