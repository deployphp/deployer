<?php

namespace Deployer\Ssh;

class RunParams
{
    /**
     * @param array<string, scalar>|null $env
     * @param array<string, scalar>|null $secrets
     */
    public function __construct(
        public readonly ?string  $shell = null,
        public readonly ?string $cwd = null,
        public readonly ?array  $env = null,
        public ?string $dotenv = null,
        public readonly bool    $nothrow = false,
        public readonly ?int    $timeout = null,
        public readonly bool    $killOnTimeout = true,
        public readonly ?int    $idleTimeout = null,
        public readonly bool    $forceOutput = false,
        #[\SensitiveParameter]
        public readonly ?array  $secrets = null,
    ) {}

    /**
     * @param array<string, scalar>|null $secrets
     */
    public function with(
        #[\SensitiveParameter]
        ?array $secrets = null,
        ?int $timeout = null,
        ?bool $killOnTimeout = null,
    ): self {
        return new self(
            $this->shell,
            $this->cwd,
            $this->env,
            $this->dotenv,
            $this->nothrow,
            $timeout ?? $this->timeout,
            $killOnTimeout ?? $this->killOnTimeout,
            $this->idleTimeout,
            $this->forceOutput,
            array_merge($this->secrets ?? [], $secrets ?? []),
        );
    }
}
