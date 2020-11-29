<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class DiceCommand extends Command
{
    use CommandCommon;

    private $deployer;

    public function __construct()
    {
        parent::__construct('roll:dice');
        $this->setDescription('Roll any number of dice');
    }

    protected function configure()
    {
        $this->addArgument('number', InputArgument::OPTIONAL, 'Number of dice', 2);
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->telemetry();
        $number = intval($input->getArgument('number'));
        while ($number-- > 0) {
            $output->write(["⚀", "⚁", "⚂", "⚃", "⚄", "⚅"][rand(0, 5)]);
        }
        $output->write("\n");
        return 0;
    }
}
