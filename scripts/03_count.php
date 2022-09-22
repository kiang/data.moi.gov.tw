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
foreach ($files as $file) {
    $count1 = $count2 = $count3 = 0;
    $fh = fopen($file, 'r');
    $line = fgetcsv($fh, 4096);
    while ($line = fgetcsv($fh, 4096)) {
        if (mb_substr($line[2], 0, 3, 'utf-8') === '臺南市') {
            $count1 += $line[5];
            for ($i = 8; $i <= 99; $i++) {
                $count2 += $line[$i];
            }
            for ($i = 38; $i <= 137; $i++) {
                $count3 += $line[$i];
            }
        }
    }
    print_r([
        'file' => $file,
        'total' => $count1,
        '45' => $count2,
        '15-64' => $count3,
        'p' => round($count2 / $count1, 2),
    ]);
}
