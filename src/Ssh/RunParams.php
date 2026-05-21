<?php

namespace Deployer\Ssh;

class RunParams
{
    /**
     * @param array<string, scalar>|null $env
     * @param array<string, scalar>|null $secrets
     */
    public function __construct(
        public ?string  $shell = null,
        public ?string $cwd = null,
        public ?array  $env = null,
        public ?string $dotenv = null,
        public bool    $nothrow = false,
        public ?int    $timeout = null,
        public bool    $killOnTimeout = true,
        public ?int    $idleTimeout = null,
        public bool    $forceOutput = false,
        #[\SensitiveParameter]
        public ?array  $secrets = null,
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
        $params = clone $this;
        $params->secrets = array_merge($params->secrets ?? [], $secrets ?? []);
        $params->timeout = $timeout ?? $params->timeout;
        $params->killOnTimeout = $killOnTimeout ?? $params->killOnTimeout;
        return $params;
    }
}
