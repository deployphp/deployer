<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\PHP as PHPReport;

$filter = new Filter();
$filter->includeDirectory('/project');
$filter->excludeDirectory('/project/vendor');
$filter->excludeDirectory('/project/tests');
$report = new PHPReport();

$coverage = new CodeCoverage(
    (new Selector)->forLineCoverage($filter),
    $filter
);

$outputDir = '/tmp/ccov';
if (!is_dir($outputDir)) {
    mkdir($outputDir);
}

// use anonymous class as we don't really want to pollute class space with this stuff
(new class ($coverage, $report, $outputDir) {
    /** @var CodeCoverage */
    private $coverage;
    /** @var PHPReport */
    private $report;
    /** @var string */
    private $outputDir;
    /** @var string|null */
    private $coverageName;

    public function __construct(CodeCoverage $coverage, PHPReport $report, string $outputDir) {
        $this->coverage = $coverage;
        $this->report = $report;
        $this->outputDir = $outputDir;
    }

    public function start():void {
        register_shutdown_function([$this, 'stop']);

        $coverageName = uniqid('coverage_');
        $this->coverageName = $coverageName;
        $this->coverage->start($this->coverageName);
    }

    public function stop():void {
        $this->coverage->stop();

        $outputFile = $this->outputDir . "/{$this->coverageName}.php";
        $this->report->process($this->coverage, $outputFile);
    }
})->start();



