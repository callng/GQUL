<?php

$filesToExtract = [
    'assets/appid.ini',
    'assets/jni.ini',
    'assets/qua.ini',
    'assets/revision.txt',
    'lib/arm64-v8a/libfekit.so'
];

/**
 * @return false|array
 */
function getParameters(): false|array
{
    $requiredParams = ['url'];
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

/**
 * @param string $url
 * @return false|array
 */
function getFileInfo(string $url): false|array
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Curl error: ' . curl_error($curl);
        return false;
    }
    curl_close($curl);
    $headers = explode("\n", $response);
    $md5 = null;
    $contentLength = null;
    foreach ($headers as $header) {
        if (str_starts_with($header, 'X-COS-META-MD5:')) {
            $md5 = trim(substr($header, strpos($header, ':') + 1));
        } elseif (str_starts_with($header, 'Content-Length:')) {
            $contentLength = trim(substr($header, strpos($header, ':') + 1));
        } elseif (str_starts_with($header, 'Etag:')) {
            $md5 = str_replace(['"', "\r", "\n"], '', substr($header, strpos($header, ':') + 2));
        }
    }
    $sizeMB = number_format($contentLength / (1024 * 1024), 2);
    return [
        'md5' => $md5,
        'sizeMB' => $sizeMB
    ];
}

/**
 * @param CurlHandle $ch
 * @param string $url
 * @param string $range
 */
function setCurlOptions(CurlHandle $ch, string $url, string $range): void
{
    $headers = ["Range: bytes=$range"];
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
}

/**
 * @param string $url
 * @param string $range
 * @return string|bool
 */
function downloadData(string $url, string $range): bool|string
{
    $ch = curl_init();
    setCurlOptions($ch, $url, $range);
    $data = curl_exec($ch);
    curl_close($ch);
    if ($data === false) {
        error_log(curl_error($ch));
        return false;
    }
    return $data;
}

/**
 * @param string $url
 * @param array $ranges
 * @return array
 */
function downloadDataParallel(string $url, array $ranges): array
{
    $mh = curl_multi_init();
    $handles = [];
    $responses = [];
    foreach ($ranges as $fileName => $range) {
        $ch = curl_init();
        setCurlOptions($ch, $url, $range);
        curl_multi_add_handle($mh, $ch);
        $handles[$fileName] = $ch;
    }
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh);
        }
    } while ($running > 0);
    foreach ($handles as $fileName => $ch) {
        $responses[$fileName] = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $responses;
}

/**
 * @param string $url
 * @return array|false
 */
function findEndOfCentralDirectory(string $url): false|array
{
    $eocdMaxSize = 65557;
    $data = downloadData($url, '-' . $eocdMaxSize);
    if (!$data) {
        return false;
    }
    $eocdPos = strrpos($data, "\x50\x4b\x05\x06");
    if ($eocdPos === false) {
        return false;
    }
    return [
        'centralDirectorySize' => unpack('V', substr($data, $eocdPos + 12, 4))[1],
        'centralDirectoryOffset' => unpack('V', substr($data, $eocdPos + 16, 4))[1]
    ];
}

/**
 * @param string $data
 * @return array
 */
function parseCentralDirectory(string $data): array
{
    $entries = [];
    $offset = 0;
    while ($offset + 4 < strlen($data)) {
        if (unpack('V', substr($data, $offset, 4))[1] !== 0x02014b50) {
            break;
        }
        $entry = [
            'compressionMethod' => unpack('v', substr($data, $offset + 10, 2))[1],
            'lastModTime' => unpack('v', substr($data, $offset + 12, 2))[1],
            'lastModDate' => unpack('v', substr($data, $offset + 14, 2))[1],
            'crc32' => unpack('V', substr($data, $offset + 16, 4))[1],
            'compressedSize' => unpack('V', substr($data, $offset + 20, 4))[1],
            'uncompressedSize' => unpack('V', substr($data, $offset + 24, 4))[1],
            'fileNameLength' => unpack('v', substr($data, $offset + 28, 2))[1],
            'extraFieldLength' => unpack('v', substr($data, $offset + 30, 2))[1],
            'fileCommentLength' => unpack('v', substr($data, $offset + 32, 2))[1],
            'fileHeaderOffset' => unpack('V', substr($data, $offset + 42, 4))[1],
            'fileName' => substr($data, $offset + 46, unpack('v', substr($data, $offset + 28, 2))[1])
        ];
        $entries[$entry['fileName']] = $entry;
        $offset += 46 + $entry['fileNameLength'] + $entry['extraFieldLength'] + $entry['fileCommentLength'];
    }
    return $entries;
}

/**
 * @param string $centralDirectoryData
 * @param string $url
 * @param array $filesToExtract
 * @return string
 */
function extractAndPrintFiles(string $centralDirectoryData, string $url, array $filesToExtract): string
{
    $entries = parseCentralDirectory($centralDirectoryData);
    $entriesToDownload = array_filter($entries, function ($entry) use ($filesToExtract) {
        return in_array($entry['fileName'], $filesToExtract);
    });
    $ranges = [];
    foreach ($entriesToDownload as $entry) {
        $fileDataOffset = $entry['fileHeaderOffset'] + 30 + $entry['fileNameLength'] + $entry['extraFieldLength'];
        $fileDataRange = $fileDataOffset . '-' . ($fileDataOffset + $entry['compressedSize'] - 1);
        $ranges[$entry['fileName']] = $fileDataRange;
    }
    $responses = downloadDataParallel($url, $ranges);
    $filesInfo = [];
    foreach ($entriesToDownload as $entry) {
        $fileName = $entry['fileName'];
        $fileData = $responses[$fileName] ?? ''; // 使用 null 合并运算符以防文件未下载
        if ($entry['compressionMethod'] == 8) {
            $fileContent = @gzinflate($fileData);
            if ($fileContent === false) {
                continue;
            }
        } elseif ($entry['compressionMethod'] == 0) {
            $fileContent = $fileData;
        } else {
            continue; // 不支持的压缩方法，跳过文件
        }
        // 处理特定文件的逻辑（如qua.ini）
        if ($fileName === 'assets/qua.ini') {
            $startingPos = strpos($fileContent, "V1_");
            if ($startingPos !== false) {
                $fileContent = substr($fileContent, $startingPos);
            }
            $fileContent = str_replace("\n", '', $fileContent);
        }
        $lastModified = date("Y-m-d H:i:s", dosToUnixTime($entry['lastModTime'], $entry['lastModDate']));
        $fileInfo = [
            'fileName' => substr($fileName, strrpos($fileName, '/') + 1),
            'crc32' => sprintf("%08x", $entry['crc32']),
            'lastModified' => ($lastModified === '1979-11-30 00:00:00') ? null : $lastModified
        ];
        if (str_contains($fileName, 'libfekit.so')) {
            $fileInfo['sizeMB'] = number_format($entry['uncompressedSize'] / (1024 * 1024), 2);
        }
        if (!strpos($entry['fileName'], '.so')) {
            $fileInfo['content'] = $fileContent;
        }
        $filesInfo[] = $fileInfo;
    }
    return json_encode($filesInfo);
}

/**
 * @param int $dosTime
 * @param int $dosDate
 * @return false|int
 */
function dosToUnixTime(int $dosTime, int $dosDate): false|int
{
    $seconds = ($dosTime & 0x1F) * 2;
    $minutes = ($dosTime >> 5) & 0x3F;
    $hours = ($dosTime >> 11) & 0x1F;
    $day = $dosDate & 0x1F;
    $month = ($dosDate >> 5) & 0x0F;
    $year = (($dosDate >> 9) & 0x7F) + 1980;
    return mktime($hours, $minutes, $seconds, $month, $day, $year);
}

header('Content-Type: application/json');
$param = getParameters();
if ($param === false) {
    die('Bad Request');
}
$apkUrl = $param['url'];
$apkUrlBase64 = base64_encode($apkUrl);
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1');
    $password = '114514';
    if (!$redis->auth($password)) {
        throw new RedisException('Redis authentication failed');
    }
    $redis->select(7);
    if ($redis->exists($apkUrlBase64)) {
        $result = $redis->get($apkUrlBase64);
        $redis->close();
        exit($result);
    }
} catch (RedisException $e) {
    $result = [
        'error' => 'Redis error：' . $e->getMessage()
    ];
    exit(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
$eocd = findEndOfCentralDirectory($apkUrl);
if (!$eocd || !isset($eocd['centralDirectoryOffset'], $eocd['centralDirectorySize'])) {
    die("Failed to retrieve EOCD information.");
}
$centralDirectoryData = downloadData($apkUrl, $eocd['centralDirectoryOffset'] . '-' . ($eocd['centralDirectoryOffset'] + $eocd['centralDirectorySize'] - 1));
if (!$centralDirectoryData) {
    die("Failed to download central directory.");
}
$fileJson = extractAndPrintFiles($centralDirectoryData, $apkUrl, $filesToExtract);
$fileInfo = getFileInfo($apkUrl);
if ($fileInfo) {
    $fileInfo['files'] = json_decode($fileJson, true);
    $result = json_encode($fileInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else {
    $result = json_encode(json_decode($fileJson, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
try {
    $redis->set($apkUrlBase64, $result, 86400 * 30 * 12);
} catch (RedisException $e) {
    $result = [
        'error' => 'Redis error：' . $e->getMessage()
    ];
    exit(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
exit($result);
