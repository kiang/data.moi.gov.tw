<?php

/**
 * ref
 * 1. https://adamboother.com/blog/automatically-posting-to-a-facebook-page-using-the-facebook-sdk-v5-for-php-facebook-api/
 * 2. https://phppot.com/php/publishing-multi-photo-stories-to-facebook-using-php-sdk/
 * 
 * generate the token
 * https://developers.facebook.com/tools/explorer/
 * 
 * permission: pages_manage_posts, pages_read_engagement, pages_show_list
 * 
 * remember to extend token expire date every 2 months
 * https://developers.facebook.com/tools/debug/accesstoken/?access_token=
 */
$basePath = dirname(__DIR__);
require_once $basePath . '/fb/vendor/autoload.php';
$config = require $basePath . '/fb/config.php';

$reports = $sum = $first = $second = [];
foreach (glob($basePath . '/docs/svg/report/2022/05/臺南市/*.json') as $jsonFile) {
    $p = pathinfo($jsonFile);
    $data = json_decode(file_get_contents($jsonFile), true);
    foreach ($data['area'] as $k => $v) {
        if (!isset($sum[$k])) {
            $sum[$k] = 0;
        }
        $sum[$k] += $v;
    }
    switch ($p['filename']) {
        case '臺南市北區':
            $first[] = [
                'data' => $data,
                'svg' => substr($jsonFile, 0, -4) . 'svg',
            ];
            break;
        case '臺南市中西區':
            $second[] = [
                'data' => $data,
                'svg' => substr($jsonFile, 0, -4) . 'svg',
            ];
            break;
        default:
            $reports[] = [
                'data' => $data,
                'svg' => substr($jsonFile, 0, -4) . 'svg',
            ];
    }
}
$reports = array_merge($first, $second, $reports);

$message = '臺南市人口速報 2022年05月';
$message .= "\n\n依據最新村里人口統計，臺南市本月人口變化為 {$sum['new_population']} 人，年度累積至本月變化為 {$sum['sum_population']} 人\n";
foreach ($reports as $report) {
    $p = pathinfo($report['svg']);
    $message .= "\n{$p['filename']} 本月 {$report['data']['area']['new_population']} 人，年度累積至本月 {$report['data']['area']['sum_population']} 人";
}
$message .= "\n\n⭐全部報表 - https://github.com/kiang/data.moi.gov.tw/tree/master/docs/svg/report/2022/05";
$message .= "\n⭐地圖 - https://tainan.olc.tw/";
$message .= "\n\n#小額捐款支持明宗 https://kiang.oen.tw";
$message .= "\n#北中西區台南市議員參選人江明宗";

$imgPath = $basePath . '/fb/tmp';
if (!file_exists($imgPath)) {
    mkdir($imgPath, 0777);
}
$fb = new Facebook\Facebook([
    'app_id' => $config['app_id'],
    'app_secret' => $config['app_secret'],
    'default_graph_version' => 'v2.2',
]);
$media = [];

foreach ($reports as $k => $report) {
    $p = pathinfo($report['svg']);
    $photoMessage = $p['filename'] . '人口速報 2022年05月';
    $photoMessage .= "\n\n{$p['filename']}本月人口變化為 {$report['data']['area']['new_population']} 人，年度累積至本月變化為 {$report['data']['area']['sum_population']} 人";
    foreach ($report['data']['cunli'] as $cunli => $cunliReport) {
        $photoMessage .= "\n✏{$cunli} 本月 {$cunliReport['new_population']} 人，年度累積至本月 {$cunliReport['sum_population']} 人";
    }
    $photoMessage .= "\n\n#小額捐款支持明宗 https://kiang.oen.tw";
    $photoMessage .= "\n#北中西區台南市議員參選人江明宗";


    $imgFile = $imgPath . '/' . $k . '.png';
    if (file_exists($imgFile)) {
        unlink($imgFile);
    }
    exec('inkscape -w 1080 -h 1350 -z --export-png=' . $imgFile . ' ' . $report['svg']);

    try {
        $response = $fb->post('/' . $config['page_id'] . '/photos', [
            'message' => $photoMessage,
            'source' => $fb->fileToUpload($imgFile),
            'published' => false,
        ], $config['token']);
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit();
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit();
    }
    $body = $response->getDecodedBody();
    if (!empty($body['id'])) {
        $media[] = ['media_fbid' => $body['id']];
    }
}

//Post property to Facebook
$linkData = [
    'message' => $message,
    'attached_media' => $media,
];

try {
    $response = $fb->post('/' . $config['page_id'] . '/feed', $linkData, $config['token']);
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}
$graphNode = $response->getGraphNode();
