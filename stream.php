<?php

include("_configs.php");
header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$payload = "";
if (isset($_REQUEST['payload'])) {
    $payload = trim($_REQUEST['payload']);
}

if (empty($payload)) {
    http_response_code(400);
    exit('Payload is required.');
}

try {
    if (stripos($payload, ".mpd") !== false) {
        $channelID = str_replace("/ps_manifest.mpd", "", $payload);
        if (empty($channelID)) {
            http_response_code(400);
            exit('Invalid channel ID.');
        }

        $cdetail = getChannelDetail($channelID);
        if (!isset($cdetail['manifest_url']) || empty($cdetail['manifest_url'])) {
            http_response_code(404);
            exit('Channel not found.');
        }

        $streamURL = $cdetail['manifest_url'];
        $keyId = $cdetail['keyId'];

        $afetch = getRequest($streamURL, array(

        ));

        $return = $afetch['data'];

        if (stripos($return, '<MPD') !== false) {
            header("Content-Type: " . $afetch['content_type']);
            print($return);
            exit();
        }
        http_response_code(503);
        exit('Service unavailable.');
    } else {
        $channelID = extractChnsIDS($payload);
        $channelPart = extractChnsPart($payload);
        if (empty($channelID)) {
            http_response_code(400);
            exit('Invalid channel ID.');
        }

        $cdetail = getChannelDetail($channelID);
        if (!isset($cdetail['manifest_url']) || empty($cdetail['manifest_url'])) {
            http_response_code(404);
            exit('Channel not found.');
        }

        $streamURL = extractRelativeBase($cdetail['manifest_url']) . $channelPart;

        $afetch = getRequest($streamURL, array(

        ));

        $return = $afetch['data'];
        if (!empty($return)) {
            header("Content-Type: " . $afetch['content_type']);
            print($return);
            exit();
        }
        http_response_code(503);
        exit('Service unavailable.');
    }
} catch (Exception $e) {
    http_response_code(500);
    exit('Internal server error: ' . $e->getMessage());
}

?>