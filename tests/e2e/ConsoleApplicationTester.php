<?php
declare(strict_types=1);

namespace e2e;

use Symfony\Component\Process\Process;

class ConsoleApplicationTester
{
    public const DEFAULT_TIMEOUT_IN_SECONDS = 10;

    private $binaryPath;
    private $cwd;
    private $timeout = self::DEFAULT_TIMEOUT_IN_SECONDS;

    private $inputs = [];

    /** @var Process|null */
    private $process = null;


    private static function createInputsStream(array $inputs)
    {
        $stream = fopen('php://memory', 'r+', false);

        foreach ($inputs as $input) {
            fwrite($stream, $input.\PHP_EOL);
        }

        rewind($stream);

        return $stream;
    }

    private function generateCommand(array $arguments): array
    {
        $arguments = array_merge([ $this->binaryPath ], $arguments);

        $outputArgs = [];
        foreach ($arguments as $key => $value) {
            if (!is_numeric($key)) {
                $outputArgs[] = $key;
            }

            $outputArgs[] = $value;
        }

        return $outputArgs;
    }

    private function prepareProcess(array $arguments): Process
    {
        $commandLine = $this->generateCommand($arguments);

        $process = new Process($commandLine);
        $process->setTimeout($this->timeout);

        if (!empty($this->inputs)) {
            $inputs = self::createInputsStream($this->inputs);
            $process->setInput($inputs);
        }

        if (!empty($this->cwd)) {
            $process->setWorkingDirectory($this->cwd);
        }

        return $process;
    }

    public function __construct(string $binaryPath, string $cwd = '')
    {
        $this->binaryPath = $binaryPath;
        $this->cwd = $cwd;
    }

    public function __destruct()
    {
        if ($this->process && $this->process->isRunning()) {
            $this->process->stop(0);
        }
    }

    /**
     * @param int $timeout timout in seconds after which process will be stopped
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setInputs(array $inputs): self
    {
        $this->inputs = $inputs;
        return $this;
    }

    public function run(array $arguments): self
    {
        if ($this->process && $this->process->isRunning()) {
            throw new \RuntimeException('Previous process did not end yet');
        }

        $this->process = $this->prepareProcess($arguments);
        $this->process->run();

        return $this;
    }

    public function getDisplay(bool $normalize = false): string
    {
        if ($this->process === null) {
            throw new \RuntimeException('Output not initialized, did you execute the command before requesting the display?');
        }

        $display = $this->process->getOutput();
        if ($normalize) {
            $display = str_replace(\PHP_EOL, "\n", $display);
        }

        return $display;
    }

    public function getErrors(bool $normalize = false): string
    {
        if ($this->process === null) {
            throw new \RuntimeException('Error output not initialized, did you execute the command before requesting the display?');
        }

        $display = $this->process->getErrorOutput();
        if ($normalize) {
            $display = str_replace(\PHP_EOL, "\n", $display);
        }

        return $display;
    }

    public function getStatusCode()
    {
        if ($this->process === null) {
            throw new \RuntimeException('Status code not initialized, did you execute the command before requesting the display?');
        }

        return $this->process->getExitCode();
    }
}
