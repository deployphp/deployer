<?php

namespace Deployer\Ssh;

class RunParams
{
    public function __construct(
        public ?string  $shell = null,
        public ?string $cwd = null,
        public ?array  $env = null,
        public ?string $dotenv = null,
        public bool    $nothrow = false,
        public ?int    $timeout = null,
        public ?int    $idleTimeout = null,
        public bool    $forceOutput = false,
        #[\SensitiveParameter]
        public ?array  $secrets = null,
    ) {}

    public function with(
        #[\SensitiveParameter]
        ?array $secrets = null,
        ?int $timeout = null,
    ): self {
        $params = clone $this;
        $params->secrets = array_merge($params->secrets ?? [], $secrets ?? []);
        $params->timeout = $timeout ?? $params->timeout;
        return $params;
    }
}
