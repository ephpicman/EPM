<?php

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$coverageDirectory = $repositoryRoot . '/coverage';
$metadataPath = $coverageDirectory . '/meta.json';
$coveragePath = $coverageDirectory . '/coverage.jsonl';
$summaryPath = getenv('GITHUB_STEP_SUMMARY');

if (false === $summaryPath || '' === $summaryPath) {
    fwrite(STDERR, "GITHUB_STEP_SUMMARY is not available.\n");
    exit(1);
}

if (!is_file($metadataPath) || !is_file($coveragePath)) {
    fwrite(STDERR, "PHPUnit JSONL coverage report is incomplete.\n");
    exit(1);
}

$metadata = json_decode((string) file_get_contents($metadataPath), true);

if (!is_array($metadata) || true !== ($metadata['branchCoverage'] ?? false)) {
    fwrite(STDERR, "Branch coverage data was not collected.\n");
    exit(1);
}

$totalLines = [
    'executed' => 0,
    'executable' => 0,
];
$totalBranches = [
    'executed' => 0,
    'total' => 0,
];
$fileMetrics = [];

$coverageFile = new SplFileObject($coveragePath);

while (!$coverageFile->eof()) {
    $record = trim($coverageFile->fgets());

    if ('' === $record) {
        continue;
    }

    $fileData = json_decode($record, true);

    if (!is_array($fileData) || !isset($fileData['file'])) {
        fwrite(STDERR, "Invalid record in PHPUnit JSONL coverage report.\n");
        exit(1);
    }

    $lines = [
        'executed' => (int) ($fileData['executed'] ?? 0),
        'executable' => (int) ($fileData['executable'] ?? 0),
    ];
    $branches = [
        'executed' => 0,
        'total' => 0,
    ];

    foreach (($fileData['symbols'] ?? []) as $symbol) {
        if (!isset($symbol['branches'])) {
            continue;
        }

        $branches['executed'] += (int) ($symbol['branches']['executed'] ?? 0);
        $branches['total'] += (int) ($symbol['branches']['total'] ?? 0);
    }

    $path = (string) $fileData['file'];
    $fileMetrics[$path] = [
        'lines' => $lines,
        'branches' => $branches,
    ];

    $totalLines['executed'] += $lines['executed'];
    $totalLines['executable'] += $lines['executable'];
    $totalBranches['executed'] += $branches['executed'];
    $totalBranches['total'] += $branches['total'];
}

if ([] === $fileMetrics || 0 === $totalLines['executable']) {
    fwrite(STDERR, "No usable line coverage metrics found in the PHPUnit report.\n");
    exit(1);
}

ksort($fileMetrics, SORT_NATURAL | SORT_FLAG_CASE);

$lineCoveragePassed = 0 < $totalLines['executable']
    && 100 * $totalLines['executed'] >= 70 * $totalLines['executable'];
$branchCoveragePassed = 0 < $totalBranches['total']
    && 100 * $totalBranches['executed'] >= 50 * $totalBranches['total'];
$coveragePassed = $lineCoveragePassed && $branchCoveragePassed;

$formatCoverage = static function (int $covered, int $total): string {
    if (0 === $total) {
        return 'N/A';
    }

    return sprintf('**%.1f%%** (%d/%d)', 100 * $covered / $total, $covered, $total);
};

$lines = [
    '## Code Coverage',
    '',
    '### Overall',
    '',
    '| Metric | Coverage |',
    '|---|---:|',
    sprintf(
        '| Line Coverage | %s |',
        $formatCoverage($totalLines['executed'], $totalLines['executable'])
    ),
    sprintf(
        '| Branch Coverage | %s |',
        $formatCoverage($totalBranches['executed'], $totalBranches['total'])
    ),
    '',
    '### Coverage Thresholds',
    '',
    '| Metric | Required | Result |',
    '|---|---:|---|',
    sprintf(
        '| Line Coverage | 70.0%% | %s |',
        $lineCoveragePassed ? '**PASS**' : '**FAIL**'
    ),
    sprintf(
        '| Branch Coverage | 50.0%% | %s |',
        $branchCoveragePassed ? '**PASS**' : '**FAIL**'
    ),
    '',
    sprintf('**Overall result: %s**', $coveragePassed ? 'PASS' : 'FAIL'),
    '',
    '### Per-file',
    '',
    '| File | Line Coverage | Branch Coverage |',
    '|---|---:|---:|',
];

foreach ($fileMetrics as $path => $metrics) {
    $escapedPath = str_replace('|', '\\|', $path);
    $lines[] = sprintf(
        '| `%s` | %s | %s |',
        $escapedPath,
        $formatCoverage($metrics['lines']['executed'], $metrics['lines']['executable']),
        $formatCoverage($metrics['branches']['executed'], $metrics['branches']['total'])
    );
}

$lines[] = '';
$lines[] = '<sub>Line coverage counts executable lines. Branch coverage counts executed basic blocks; both are collected by PHPUnit using Xdebug.</sub>';

if (false === file_put_contents($summaryPath, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND)) {
    fwrite(STDERR, "Unable to write the GitHub Actions job summary.\n");
    exit(1);
}

echo "Coverage report added to the GitHub Actions job summary.\n";

if (!$coveragePassed) {
    fwrite(STDERR, "Coverage thresholds not met: Line coverage must be at least 70.0% and branch coverage must be at least 50.0%.\n");
    exit(1);
}

echo "Coverage thresholds passed.\n";
