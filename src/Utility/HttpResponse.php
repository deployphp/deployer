<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Exception\HttpieException;

class HttpResponse
{
    private string $body;
    private array $info;

    public function __construct(string $body, array $info)
    {
        $this->body = $body;
        $this->info = $info;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): int
    {
        return (int) ($this->info['http_code'] ?? 0);
    }

    public function json(): mixed
    {
        return json_decode($this->body, true, flags: JSON_THROW_ON_ERROR);
    }

    public function info(): array
    {
        return $this->info;
    }

    public function __toString(): string
    {
        return $this->body;
    }
}
