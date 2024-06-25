<?php

function postJsonWithCurl($url, $data): bool|string
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept-Encoding: gzip'
    ));
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response === false) {
        return false;
    }
    return $response;
}

function getParameters(): false|array
{
    $requiredParams = ['uin', 'appid', 'version'];
    $result = [];
    foreach ($requiredParams as $param) {
        if (!empty($_GET[$param])) {
            $result[$param] = $_GET[$param];
        } else {
            return false;
        }
    }
    return $result;
}