<?php

declare(strict_types=1);

$repositoryRoot = dirname(__DIR__);
$sourceRoot = $repositoryRoot . '/src';
$coveragePath = $repositoryRoot . '/coverage.xml';
$reportPath = $repositoryRoot . '/COVERAGE.md';

if (!is_dir($sourceRoot)) {
    fwrite(STDERR, "Source directory not found: {$sourceRoot}\n");
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
    'methods',
    'coveredmethods',
    'conditionals',
    'coveredconditionals',
];
$coverageByFile = [];

foreach (($coverageXml->xpath('//file') ?: []) as $fileNode) {
    $reportedPath = str_replace('\\', '/', (string) $fileNode['name']);
    $marker = '/src/';
    $markerPosition = strrpos($reportedPath, $marker);

    if (false !== $markerPosition) {
        $relativePath = substr($reportedPath, $markerPosition + strlen($marker));
    } elseif (0 === strpos($reportedPath, 'src/')) {
        $relativePath = substr($reportedPath, strlen('src/'));
    } else {
        continue;
    }

    $fileMetrics = array_fill_keys($metricNames, 0);

    if (isset($fileNode->metrics)) {
        foreach ($metricNames as $metric) {
            $fileMetrics[$metric] = (int) $fileNode->metrics[$metric];
        }
    } else {
        foreach ($fileNode->class as $classNode) {
            if (!isset($classNode->metrics)) {
                continue;
            }

            foreach ($metricNames as $metric) {
                $fileMetrics[$metric] += (int) $classNode->metrics[$metric];
            }
        }
    }

    $coverageByFile[$relativePath] = $fileMetrics;
}

$sourceFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || 'php' !== $file->getExtension()) {
        continue;
    }

    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceRoot) + 1));
    $sourceFiles[$relativePath] = $relativePath;
}

ksort($sourceFiles, SORT_NATURAL | SORT_FLAG_CASE);

$formatCoverage = static function (int $covered, int $total): string {
    if (0 === $total) {
        return '';
    }

    $percentage = 100 * $covered / $total;
    $color = match (true) {
        $percentage < 60 => 'red',
        $percentage < 80 => 'yellow',
        $percentage < 90 => 'green',
        default => 'blue',
    };
    $label = rawurlencode(sprintf('%d/%d (%.1f%%)', $covered, $total, $percentage));

    return '![' . sprintf('%.1f%% coverage', $percentage) . '](https://img.shields.io/badge/coverage-' . $label . '-' . $color . ')';
};

$totalMetrics = array_fill_keys($metricNames, 0);
$rows = [];
$matchedFiles = 0;

foreach ($sourceFiles as $relativePath) {
    $metrics = $coverageByFile[$relativePath] ?? array_fill_keys($metricNames, 0);

    if (isset($coverageByFile[$relativePath])) {
        ++$matchedFiles;
    }

    foreach ($metricNames as $metric) {
        $totalMetrics[$metric] += $metrics[$metric];
    }

    $className = preg_replace('/\.php$/', '', $relativePath);
    $displayClassName = str_replace('/', '\\', $className);
    $encodedPath = str_replace('%2F', '/', rawurlencode($relativePath));
    $sourceUrl = 'https://github.com/ephpicman/Framework/blob/main/src/' . $encodedPath;
    $testRelativePath = preg_replace('/\.php$/', 'Test.php', $relativePath);
    $testPath = $repositoryRoot . '/tests/' . $testRelativePath;
    $testUrl = 'https://github.com/ephpicman/Framework/blob/main/tests/' . str_replace('%2F', '/', rawurlencode($testRelativePath));
    $testLink = is_file($testPath) ? '[↗](' . $testUrl . ' "View test")' : '';

    $rows[] = sprintf(
        '| [%s](%s) | %s | %s | %s | %s |',
        $displayClassName,
        $sourceUrl,
        $testLink,
        $formatCoverage($metrics['coveredstatements'], $metrics['statements']),
        $formatCoverage($metrics['coveredmethods'], $metrics['methods']),
        $formatCoverage($metrics['coveredconditionals'], $metrics['conditionals'])
    );
}

if (0 === $matchedFiles || 0 === $totalMetrics['statements']) {
    fwrite(
        STDERR,
        sprintf(
            "No usable coverage metrics found for src (matched %d of %d source files). Refusing to write an empty report.\n",
            $matchedFiles,
            count($sourceFiles)
        )
    );
    exit(1);
}

$summary = sprintf(
    '| **Total** |  | %s | %s | %s |',
    $formatCoverage($totalMetrics['coveredstatements'], $totalMetrics['statements']),
    $formatCoverage($totalMetrics['coveredmethods'], $totalMetrics['methods']),
    $formatCoverage($totalMetrics['coveredconditionals'], $totalMetrics['conditionals'])
);

$generatedContent = implode("\n", [
    '| Class | Test | Statements | Methods | Conditionals |',
    '|---|---|---|---|---|',
    ...$rows,
    $summary,
]);

$startMarker = '<!-- BEGIN GENERATED COVERAGE -->';
$endMarker = '<!-- END GENERATED COVERAGE -->';
$report = file_get_contents($reportPath);

if (false === $report) {
    fwrite(STDERR, "Unable to read COVERAGE.md.\n");
    exit(1);
}

$pattern = '/' . preg_quote($startMarker, '/') . '.*?' . preg_quote($endMarker, '/') . '/s';
$replacement = $startMarker . "\n\n" . $generatedContent . "\n\n" . $endMarker;
$updatedReport = preg_replace($pattern, $replacement, $report, 1, $replacementCount);

if (1 !== $replacementCount || null === $updatedReport) {
    fwrite(STDERR, "COVERAGE.md coverage markers are missing or invalid.\n");
    exit(1);
}

if ($updatedReport === $report) {
    echo "Coverage report is unchanged; COVERAGE.md was not modified.\n";
    exit(0);
}

if (false === file_put_contents($reportPath, $updatedReport)) {
    fwrite(STDERR, "Unable to update COVERAGE.md.\n");
    exit(1);
}

echo sprintf("COVERAGE.md updated (%d/%d source files matched).\n", $matchedFiles, count($sourceFiles));
