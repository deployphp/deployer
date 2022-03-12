<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use Deployer\Utility\Httpie;
use Symfony\Component\Process\PhpProcess;

/**
 * @codeCoverageIgnore
 */
class Reporter
{
    public static function report(array $stats): void
    {
        $version = DEPLOYER_VERSION;
        $body = json_encode($stats);
        $length = strlen($body);
        $php = new PhpProcess(<<<EOF
<?php
\$ch = curl_init('https://deployer.org/api/stats');
curl_setopt(\$ch, CURLOPT_USERAGENT, 'Deployer/$version');
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: $length',
]);
curl_setopt(\$ch, CURLOPT_POSTFIELDS, '$body');
curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt(\$ch, CURLOPT_MAXREDIRS, 10);
curl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt(\$ch, CURLOPT_TIMEOUT, 5);
\$result = curl_exec(\$ch);
curl_close(\$ch);
EOF);
        $php->start();
    }
}
