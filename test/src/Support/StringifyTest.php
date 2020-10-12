<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

class StringifyTest extends TestCase
{
    public function testOptions()
    {
        $definition = new InputDefinition([
            new Option('option', 'o', Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY, 'Set configuration option'),
            new Option('limit', 'l', Option::VALUE_REQUIRED, 'How many tasks to run in parallel?'),
            new Option('no-hooks', null, Option::VALUE_NONE, 'Run tasks without after/before hooks'),
            new Option('plan', null, Option::VALUE_NONE, 'Show execution plan'),
            new Option('start-from', null, Option::VALUE_REQUIRED, 'Start execution from this task'),
            new Option('log', null, Option::VALUE_REQUIRED, 'Write log to a file'),
            new Option('profile', null, Option::VALUE_REQUIRED, 'Write profile to a file',),
        ]);

        self::assertEquals("--option 'env=prod' --limit 1 -vvv", Stringify::options(
            new ArgvInput(['deploy', '-o', 'env=prod', '-l1'], $definition),
            new ConsoleOutput(Output::VERBOSITY_DEBUG, false)
        ));
    }
}
