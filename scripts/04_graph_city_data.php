<?php
$basePath = dirname(__DIR__);

$pngPath = $basePath . '/docs/png/population_county';
if (!file_exists($pngPath)) {
    mkdir($pngPath, 0777, true);
}

foreach (glob($basePath . '/docs/csv/population_county/*/*.csv') as $csvFile) {
    unlink($csvFile);
}

foreach (glob($basePath . '/raw/population/*/*/*.csv') as $csvFile) {
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 8000);
    if ($head[1] !== '區域別') {
        $head = fgetcsv($fh, 8000);
    }
    $head[0] = '統計年月';
    $pool = [];
    while ($line = fgetcsv($fh, 8000)) {
        if (!is_numeric($line[0])) {
            continue;
        }
        $data = array_combine($head, $line);
        $data['區域別'] = str_replace([' ', '　'], ['', ''], $data['區域別']);
        $ym = (substr($data['統計年月'], 0, 3) + 1911) . substr($data['統計年月'], 3, 2);

        if (!isset($pool[$data['區域別']])) {
            $pool[$data['區域別']] = [
                $ym,
                '0-14' => 0,
                '15-64' => 0,
                '65' => 0,
            ];
        }
        foreach ($data as $k => $v) {
            if (false !== strpos($k, '歲')) {
                $age = intval($k);
                if ($age < 15) {
                    $pool[$data['區域別']]['0-14'] += $v;
                } elseif ($age < 65) {
                    $pool[$data['區域別']]['15-64'] += $v;
                } else {
                    $pool[$data['區域別']]['65'] += $v;
                }
            }
        }
    }
    foreach ($pool as $city => $line) {
        $county = mb_substr($city, 0, 3, 'utf-8');
        $oPath = $basePath . '/docs/csv/population_county/' . $county;
        if (!file_exists($oPath)) {
            mkdir($oPath, 0777, true);
        }
        $oFile = $oPath . '/' . $city . '.csv';
        if (!file_exists($oFile)) {
            $oFh = fopen($oFile, 'w');
            fputcsv($oFh, ['ym', '0-14', '15-64', '65']);
        } else {
            $oFh = fopen($oFile, 'a');
        }
        fputcsv($oFh, $line);
        fclose($oFh);
    }
}

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
foreach (glob($basePath . '/docs/csv/population_county/*/*.csv') as $csvFile) {
    $p = pathinfo($csvFile);
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        $chart['labels'][$line[0]] = $line[0];
        $chart['datasets'][0]['data'][] = $line[1];
        $chart['datasets'][1]['data'][] = $line[2];
        $chart['datasets'][2]['data'][] = $line[3];
    }
    $chart['labels'] = array_values($chart['labels']);
    file_put_contents($basePath . '/tmp/chart.json', json_encode([
        'title' => $p['filename'] . '人口變化圖',
        'data' => $chart,
        'pngFilePath' => $pngPath . '/' . $p['filename'] . '.png',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    exec("/usr/bin/node {$basePath}/scripts/rawCharts.js");
}
