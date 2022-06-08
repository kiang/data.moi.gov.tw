<?php
/*
old bdmd:
https://data.gov.tw/api/v2/rest/dataset/8411
https://data.gov.tw/api/v2/rest/dataset/77140

old population:
https://data.gov.tw/api/v2/rest/dataset/32973
*/

$rawPath = dirname(__DIR__) . '/raw';

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/131138'), true);
foreach($json['result']['distribution'] AS $item) {
  if($item['resourceFormat'] === 'CSV') {
    $item['resourceDescription'] = trim($item['resourceDescription']);
    $y = intval(substr($item['resourceDescription'], 0, 3)) + 1911;
    if($y > 2000) {
      $pos = strpos($item['resourceDescription'], '月');
      $m = substr($item['resourceDescription'], 3, 2);
      $targetPath = $rawPath . "/bdmd/{$y}/{$m}";
      if (!file_exists($targetPath)) {
        mkdir($targetPath, 0777, true);
      }
      $targetFile = $targetPath . '/data.csv';
      if(!file_exists($targetFile)) {
        $content = shell_exec("curl '{$item['resourceDownloadUrl']}' -H 'Host: data.moi.gov.tw' -H 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' --compressed -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'");
        if(!empty($content)) {
          file_put_contents($targetFile, $content);
        }
      }
    }
  }
}

$json = json_decode(file_get_contents('https://data.gov.tw/api/v2/rest/dataset/77132'), true);
foreach($json['result']['distribution'] AS $item) {
  if($item['resourceFormat'] === 'CSV') {
    $item['resourceDescription'] = trim($item['resourceDescription']);
    $y = intval(substr($item['resourceDescription'], 0, 3)) + 1911;
    if($y > 2000) {
      $pos = strpos($item['resourceDescription'], '月');
      if(false !== $pos) {
        $part1 = explode('年', $item['resourceDescription']);
        $part2 = explode('月', $part1[1]);
        $m = str_pad($part2[0], 2, '0', STR_PAD_LEFT);
      } else {
        $m = substr($item['resourceDescription'], 3, 2);
      }
      $targetPath = $rawPath . "/population/{$y}/{$m}";
      if (!file_exists($targetPath)) {
        mkdir($targetPath, 0777, true);
      }
      $targetFile = $targetPath . '/data.csv';
      if(!file_exists($targetFile)) {
        $content = shell_exec("curl '{$item['resourceDownloadUrl']}' -H 'Host: data.moi.gov.tw' -H 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' --compressed -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'");
        if(!empty($content)) {
          file_put_contents($targetFile, $content);
        }
      }
    }
  }
}