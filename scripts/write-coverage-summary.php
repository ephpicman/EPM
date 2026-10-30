<?php

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$coveragePath = $repositoryRoot . '/coverage.xml';
$summaryPath = getenv('GITHUB_STEP_SUMMARY');

if (false === $summaryPath || '' === $summaryPath) {
    fwrite(STDERR, "GITHUB_STEP_SUMMARY is not available.\n");
    exit(1);
}

if (!is_file($coveragePath)) {
    fwrite(STDERR, "Coverage report not found: {$coveragePath}\n");
    exit(1);
}

$coverageXml = simplexml_load_file($coveragePath);

if (false === $coverageXml) {
    fwrite(STDERR, "Unable to parse Clover coverage report.\n");
    exit(1);
}

$metricNames = [
    'statements',
    'coveredstatements',
    'conditionals',
    'coveredconditionals',
];
$totalMetrics = array_fill_keys($metricNames, 0);
$fileMetrics = [];

foreach (($coverageXml->xpath('//file') ?: []) as $fileNode) {
    $reportedPath = str_replace('\\\\', '/', (string) $fileNode['name']);
    $marker = '/src/';
    $markerPosition = strrpos($reportedPath, $marker);

    if (false !== $markerPosition) {
        $relativePath = substr($reportedPath, $markerPosition + strlen($marker));
    } elseif (0 === strpos($reportedPath, 'src/')) {
        $relativePath = substr($reportedPath, strlen('src/'));
    } else {
        continue;
    }

    $metrics = array_fill_keys($metricNames, 0);

    if (isset($fileNode->metrics)) {
        foreach ($metricNames as $metric) {
            $metrics[$metric] = (int) $fileNode->metrics[$metric];
        }
    } else {
        foreach ($fileNode->class as $classNode) {
            if (!isset($classNode->metrics)) {
                continue;
            }

            foreach ($metricNames as $metric) {
                $metrics[$metric] += (int) $classNode->metrics[$metric];
            }
        }
    }

    $fileMetrics[$relativePath] = $metrics;

    foreach ($metricNames as $metric) {
        $totalMetrics[$metric] += $metrics[$metric];
    }
}

if ([] === $fileMetrics || 0 === $totalMetrics['statements']) {
    fwrite(STDERR, "No usable line coverage metrics found in the Clover report.\n");
    exit(1);
}

ksort($fileMetrics, SORT_NATURAL | SORT_FLAG_CASE);

$formatCoverage = static function (int $covered, int $total): string {
    if (0 === $total) {
        return 'N/A';
    }

    return sprintf('%d/%d (%.1f%%)', $covered, $total, 100 * $covered / $total);
};

$lines = [
    '## Code Coverage',
    '',
    '| Scope | Line Coverage | Branch Coverage |',
    '|---|---:|---:|',
    sprintf(
        '| **Total** | **%s** | **%s** |',
        $formatCoverage($totalMetrics['coveredstatements'], $totalMetrics['statements']),
        $formatCoverage($totalMetrics['coveredconditionals'], $totalMetrics['conditionals'])
    ),
    '',
    '| File | Line Coverage | Branch Coverage |',
    '|---|---:|---:|',
];

foreach ($fileMetrics as $path => $metrics) {
    $escapedPath = str_replace('|', '\\|', $path);
    $lines[] = sprintf(
        '| `%s` | %s | %s |',
        $escapedPath,
        $formatCoverage($metrics['coveredstatements'], $metrics['statements']),
        $formatCoverage($metrics['coveredconditionals'], $metrics['conditionals'])
    );
}

$lines[] = '';
$lines[] = '<sub>Line coverage is reported by PHPUnit as executable statements. Branch coverage is reported as conditionals. Branch collection requires Xdebug.</sub>';

if (false === file_put_contents($summaryPath, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND)) {
    fwrite(STDERR, "Unable to write the GitHub Actions job summary.\n");
    exit(1);
}

echo "Coverage report added to the GitHub Actions job summary.\n";
