<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Clover;

if (!isset($_SERVER['PHP_CCOV_OUTPUT_FILE']) || empty($_SERVER['PHP_CCOV_OUTPUT_FILE'])) {
    throw new \Exception("'PHP_CCOV_OUTPUT_FILE' env variable is not set!");
}

$outputFile = $_SERVER['PHP_CCOV_OUTPUT_FILE'];

$filter = new Filter();
$filter->includeDirectory('/project');
$filter->excludeDirectory('/project/vendor');
$filter->excludeDirectory('/project/tests');

$outputCoverage = new CodeCoverage(
    (new Selector)->forLineCoverage($filter),
    $filter
);

$coverageReports = glob("/tmp/ccov/*.php");
foreach ($coverageReports as $reportPath) {
    /** @var CodeCoverage $partialCoverage */
    $partialCoverage = include $reportPath;
    if (!$partialCoverage) {
        throw new \Exception("Failed to load coverage report from file '{$reportPath}'");
    }
    $outputCoverage->merge($partialCoverage);
}

$cloverReport = new Clover();
$cloverReport->process($outputCoverage, $outputFile);

echo "Clover report file written to {$outputFile}\n";

