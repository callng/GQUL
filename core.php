<?php

/**
 * 生成一个随机的UUID
 *
 * @return string 返回生成的UUID
 */
function generateRandomUUID(): string
{
    if (!function_exists('openssl_random_pseudo_bytes')) {
        error_log('请开启openssl扩展');
        return false;
    }
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return sprintf(
        '%08s-%04s-%04s-%04s-%12s',
        bin2hex(substr($data, 0, 4)),
        bin2hex(substr($data, 4, 2)),
        bin2hex(substr($data, 6, 2)),
        bin2hex(substr($data, 8, 2)),
        bin2hex(substr($data, 10, 6))
    );
}

/**
 * 生成加密前的 JSON 字符串
 *
 * @param string $appVersion 应用版本号
 * @param string $uin QQ 号
 * @param string|null $appid
 * @param string $targetApp 目标应用
 * @return string 返回生成的 JSON 字符串
 */
function generateJsonString(string $appVersion, string $uin, ?string $appid = null, string $targetApp = 'QQ'): string
{
    $timestamp = time();
    $isTim = strcasecmp($targetApp, "TIM") == 0;
    $appID = $isTim ? "ad6b501b0e" : "4cd6974be1";
    $signID = $isTim ? "33641818-aee7-445a-82d4-b7d0bce3a85a" : "0ccc46ca-154c-4c6b-8b0b-4d8537ffcbcc";
    $bundleId = $isTim ? "com.tencent.tim" : "com.tencent.mobileqq";
    $data = array(
        "systemID" => "10016",
        "appID" => $appID,
        "sign" => md5('10016$4cd6974be1$4$$' . $timestamp . '$' . $uin . '$rdelivery' . $signID),
        "timestamp" => $timestamp,
        "pullType" => 4,
        "target" => 1,
        "pullParams" => array(
            "properties" => array(
                "platform" => 2,
                "language" => "zh",
                "sdkVersion" => "1.3.36-RC01",
                "guid" => $uin,
                "appVersion" => $appVersion,
                "osVersion" => "34",
                "is64Bit" => true,
                "bundleId" => $bundleId,
                "uniqueId" => generateRandomUUID(),
                "model" => "2304FPN6DC"
            ),
            "isDebugPackage" => false,
            "customProperties" => array(
                "appid" => !is_null($appid) ? $appid : "537230561",
            )
        ),
        "taskChecksum" => "0",
        "context" => "H4sIAAAAAAAA/+Li5ni5T1WIVaBT1INRS8HS0MwyMdnCwMzQMCklxdQ81cTC1MzIIDnV0DIxydLYGAAAAP//AQAA//+OoFcLLwAAAA=="
    );
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 解析JSON并返回第一个cipher_text的数据
 *
 * @param string $jsonString 待解析的JSON字符串
 * @return string|bool 成功时返回cipher_text内容，失败时返回false
 */
function getCipherText(string $jsonString): string|bool
{
    $data = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    if (!empty($data['rsp_list'])) {
        foreach ($data['rsp_list'] as $value) {
            if (isset($value['cipher_text'])) {
                return $value['cipher_text'];
            }
        }
    }
    return false;
}
