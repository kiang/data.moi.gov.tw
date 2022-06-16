<?php

$basePath = dirname(__DIR__);

$files = [
    $basePath . '/raw/population/2021/12/data.csv' => 'population',
    $basePath . '/raw/population/2022/04/data.csv' => 'population',
    $basePath . '/raw/population/2022/05/data.csv' => 'population',
];

for ($i = 1; $i <= 5; $i++) {
    $file = $basePath . '/raw/bdmd/2022/' . str_pad($i, 2, '0', STR_PAD_LEFT) . '/data.csv';
    $files[$file] = 'bdmd';
}


/*
population
    [0] => statistic_yyymm
    [1] => district_code
    [2] => site_id
    [3] => village
    [4] => household_no
    [5] => people_total
    [6] => people_total_m
    [7] => people_total_f
bdmd
    [0] => statistic_yyymm
    [1] => district_code
    [2] => site_id
    [3] => village
    [4] => birth_total
    [5] => birth_total_m
    [6] => birth_total_f
    [7] => death_total
    [8] => death_m
    [9] => death_f
*/


$pool = [];
$toSkip = ['site_id', '區域別'];
foreach ($files as $file => $type) {
    $fh = fopen($file, 'r');
    while ($line = fgetcsv($fh, 8000)) {
        if (in_array($line[2], $toSkip)) {
            continue;
        }
        $area = $line[2];
        $cunli = $line[3];
        if ($cunli === '南雄里') {
            $cunli = '龜洞里';
        }
        if (!isset($pool[$area])) {
            $pool[$area] = [];
        }
        if (!isset($pool[$area][$cunli])) {
            $pool[$area][$cunli] = [];
        }
        if ($type === 'bdmd') {
            $pool[$area][$cunli][$line[0] . '_birth_total'] = $line[4];
            $pool[$area][$cunli][$line[0] . '_death_total'] = $line[7];
        } else {
            $pool[$area][$cunli][$line[0]] = $line[5];
        }
    }
}

function cmp($a, $b)
{
    if ($a['sum_population'] == $b['sum_population']) {
        return 0;
    }
    return ($a['sum_population'] > $b['sum_population']) ? -1 : 1;
}

$reportTemplate = file_get_contents($basePath . '/art/base.svg');

foreach ($pool as $area => $cunlis) {
    $data = [
        'new_death' => 0,
        'new_population' => 0,
        'new_birth' => 0,
        'sum_death' => 0,
        'sum_population' => 0,
        'sum_birth' => 0,
    ];
    foreach ($cunlis as $cunliKey => $cunli) {
        $cunlis[$cunliKey]['new_population'] = $cunli['11105'] - $cunli['11104'];
        $cunlis[$cunliKey]['sum_population'] = $cunli['11105'] - $cunli['11012'];
        $data['new_death'] += $cunli['11105_death_total'];
        $data['new_birth'] += $cunli['11105_birth_total'];
        $data['new_population'] += $cunlis[$cunliKey]['new_population'];
        $data['sum_population'] += $cunlis[$cunliKey]['sum_population'];
        foreach ($cunli as $k => $v) {
            if (false !== strpos($k, 'death_total')) {
                $data['sum_death'] += $v;
            } elseif (false !== strpos($k, 'birth_total')) {
                $data['sum_birth'] += $v;
            }
        }
    }
    $report = strtr($reportTemplate, [
        '{{report_title}}' => $area,
        '{{report_date}}' => '2022年5月',
        '{{new_death}}' => $data['new_death'],
        '{{new_population}}' => $data['new_population'],
        '{{new_birth}}' => $data['new_birth'],
        '{{sum_death}}' => $data['sum_death'],
        '{{sum_population}}' => $data['sum_population'],
        '{{sum_birth}}' => $data['sum_birth'],
    ]);
    $areaData = $data;
    $pos = strpos($report, '{{loop_begin}}');
    $posEnd = strpos($report, '{{loop_end}}');
    $reportEnd = substr($report, $posEnd  + 14);
    $cunliTemplate = substr($report, $pos + 14, $posEnd - $pos - 14);

    $loopY = 0;
    $report = substr($report, 0, $pos);
    uasort($cunlis, 'cmp');
    $loopX1 = 0;
    $loopX2 = 200;
    $count = 0;
    foreach ($cunlis as $cunli => $data) {
        if (++$count > 44) {
            continue;
        }
        $report .= strtr($cunliTemplate, [
            '{{loop_x1}}' => $loopX1,
            '{{loop_x2}}' => $loopX2,
            '{{loop_y}}' => $loopY,
            '{{loop_cunli}}' => $cunli,
            '{{loop_text}}' => "年 {$data['sum_population']} ， 月 {$data['new_population']}",
        ]);
        $loopY += 70;
        if ($loopY > 1470) {
            $loopY = 0;
            $loopX1 = 800;
            $loopX2 = 1000;
        }
    }
    $svgPath = $basePath . '/docs/svg/report/2022/05/' . mb_substr($area, 0, 3, 'utf-8');
    if (!file_exists($svgPath)) {
        mkdir($svgPath, 0777, true);
    }
    file_put_contents($svgPath . '/' . $area . '.svg', $report . $reportEnd);
    file_put_contents($svgPath . '/' . $area . '.json', json_encode([
        'area' => $areaData,
        'cunli' => $cunlis,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
