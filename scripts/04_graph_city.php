<?php
$basePath = dirname(__DIR__);
$pngPath = $basePath . '/docs/png/population_county';

foreach (glob($basePath . '/docs/csv/population_county/*/*.csv') as $csvFile) {
    $chart = [
        'labels' => [],
        'datasets' => [
            [
                'label' => '未滿15歲',
                'backgroundColor' => 'rgb(255, 99, 132)',
                'data' => [],
            ],
            [
                'label' => '15-64歲',
                'backgroundColor' => 'rgb(54, 162, 235)',
                'data' => [],
            ],
            [
                'label' => '年滿65歲',
                'backgroundColor' => 'rgb(201, 203, 207)',
                'data' => [],
            ]
        ],
    ];
    $p = pathinfo($csvFile);
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        $chart['labels'][$line[0]] = $line[0];
        $chart['datasets'][0]['data'][$line[0]] = $line[1];
        $chart['datasets'][1]['data'][$line[0]] = $line[2];
        $chart['datasets'][2]['data'][$line[0]] = $line[3];
    }
    ksort($chart['labels']);
    $chart['labels'] = array_values($chart['labels']);
    foreach ($chart['datasets'] as $k => $v) {
        ksort($chart['datasets'][$k]['data']);
        $chart['datasets'][$k]['data'] = array_values($chart['datasets'][$k]['data']);
    }
    $city = mb_substr($p['filename'], 0, 3, 'utf-8');
    $filePath = $pngPath . '/' . $city;
    if(!file_exists($filePath)) {
        mkdir($filePath, 0777, true);
    }
    file_put_contents($basePath . '/tmp/chart.json', json_encode([
        'title' => $p['filename'] . '人口變化圖',
        'data' => $chart,
        'pngFilePath' => $filePath . '/' . $p['filename'] . '.png',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    exec("/usr/bin/node {$basePath}/scripts/rawCharts.js");
}
