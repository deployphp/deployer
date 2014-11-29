<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class Application extends Console
{
    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        $inputDefinition->addOption(
            new InputOption('--file', '-f', InputOption::VALUE_NONE, 'Specify Deployer file.')
        );

        return $inputDefinition;
    }

} 
