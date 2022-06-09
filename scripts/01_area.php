<?php
$rawPath = dirname(__DIR__) . '/raw';
$targetPath = $rawPath . "/area";
if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777, true);
}

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/8410'), true);
foreach ($json['result']['distribution'] as $item) {
    if ($item['resourceFormat'] === 'CSV') {
        $item['resourceDescription'] = trim($item['resourceDescription']);
        $y = intval(substr($item['resourceDescription'], 0, 3)) + 1911;
        if ($y > 2000) {
            $targetFile = $targetPath . '/' . $y . '.csv';
            if (!file_exists($targetFile)) {
                $content = shell_exec("curl '{$item['resourceDownloadUrl']}' -H 'Host: data.moi.gov.tw' -H 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' --compressed -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'");
                if (!empty($content)) {
                    file_put_contents($targetFile, $content);
                }
            }
        }
    }
}
