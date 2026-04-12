<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Support\Reporter;

use function Deployer\Support\ci_name;

trait CommandCommon
{
    /**
     * @codeCoverageIgnore
     */
    protected function telemetry(array $data = []): void
    {
        if (getenv('DO_NOT_TRACK') === 'true') {
            return;
        }
        try {
            Reporter::report(array_merge([
                'command_name' => $this->getName(),
                'deployer_version' => DEPLOYER_VERSION,
                'deployer_phar' => Deployer::isPharArchive(),
                'php_version' => phpversion(),
                'os' => defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY : (stristr(PHP_OS, 'DAR') ? 'OSX' : (stristr(PHP_OS, 'WIN') ? 'WIN' : (stristr(PHP_OS, 'LINUX') ? 'LINUX' : PHP_OS))),
                'ci' => ci_name(),
            ], $data));
        } catch (\Throwable $e) {
            return;
        }
    }

}
