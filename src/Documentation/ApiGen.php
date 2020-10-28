<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Documentation;

class ApiGen
{
    private $fns = [];

    public function parse(string $source): void
    {
        $comment = '';
        $params = '';

        $state = 'root';
        foreach (explode("\n", $source) as $lineNumber => $line) {
            switch ($state) {
                case 'root':
                    if (str_starts_with($line, '/**')) {
                        $state = 'comment';
                        break;
                    }
                    if (str_starts_with($line, 'function')) {
                        $signature = preg_replace('/^function\s+/', '', $line);
                        $funcName = preg_replace('/\(.+$/', '', $signature);
                        $this->fns[] = [
                            'comment' => $comment,
                            'params' => $params,
                            'funcName' => $funcName,
                            'signature' => $signature,
                        ];
                        $comment = '';
                        $params = '';
                        break;
                    }
                    break;

                case 'comment':
                    if (str_ends_with($line, '*/')) {
                        $state = 'root';
                        break;
                    }
                    if (str_starts_with($line, ' * @')) {
                        $params .= $line . "\n";
                        break;
                    }
                    $comment .= preg_replace('/^\s\*\s?/', '', $line) . "\n";
                    break;
            }
        }
    }

    public function markdown(): string
    {
        $output = <<<MD
<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit src/functions.php -->
<!-- Then run bin/docgen -->

# API Reference


MD;

        foreach ($this->fns as $fn) {
            ['funcName' => $funcName] = $fn;
            $output .= " * [`$funcName()`](#$funcName)\n";
        }
        $output .= "\n";

        foreach ($this->fns as $fn) {
            [
                'comment' => $comment,
                'params' => $params,
                'funcName' => $funcName,
                'signature' => $signature,
            ] = $fn;

            $output .= <<<MD
## $funcName()

```php
$signature
```

$comment

MD;
        }
        return $output;
    }
}
