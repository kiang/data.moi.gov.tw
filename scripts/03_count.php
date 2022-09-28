<?php

$basePath = dirname(__DIR__);

$files = [
    $basePath . '/raw/population/2018/12/data.csv',
    $basePath . '/raw/population/2022/08/data.csv',
];
/*
[5] => people_total
8-99 => <= 45
38-137 => 15-64
*/
$pool = [];
foreach ($files as $file) {
    $count1 = $count2 = $count3 = 0;
    $fh = fopen($file, 'r');
    fgetcsv($fh, 4096);
    fgetcsv($fh, 4096);
    while ($line = fgetcsv($fh, 4096)) {
        $city = mb_substr($line[2], 0, 3, 'utf-8');
        $year = substr($line[0], 0, 3) + 1911;
        if (!isset($pool[$city])) {
            $pool[$city] = [];
        }
        if (!isset($pool[$city][$year])) {
            $pool[$city][$year] = [
                'total' => 0,
                '45' => 0,
                '15-64' => 0,
                'p' => 0.0,
            ];
        }
        $pool[$city][$year]['total'] += $line[5];
        for ($i = 8; $i <= 99; $i++) {
            $pool[$city][$year]['45'] += $line[$i];
        }
        for ($i = 38; $i <= 137; $i++) {
            $pool[$city][$year]['15-64'] += $line[$i];
        }
        $pool[$city][$year]['p'] = round($pool[$city][$year]['15-64'] / $pool[$city][$year]['45'], 2);
    }
}
foreach ($pool as $city => $lv1) {
    $pool[$city]['change'] = [
        'total' => $pool[$city][2022]['total'] - $pool[$city][2018]['total'],
        '45' => $pool[$city][2022]['45'] - $pool[$city][2018]['45'],
        '15-64' => $pool[$city][2022]['15-64'] - $pool[$city][2018]['15-64'],
        'p' => round(($pool[$city][2022]['45'] - $pool[$city][2018]['45']) / $pool[$city][2018]['45'], 2),
    ];
}
print_r($pool);
