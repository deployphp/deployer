<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Input;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

final class ArgumentTest extends TestCase
{
    public function toStringProvider(): \Generator
    {
        foreach ([
                     ['fooBar', 'fooBar'],
                     ['0', '0'],
                     ['1', '1'],
                     ['foo\-%&Bar', 'foo\-%&Bar'],
                     ['\'', '\''],
                     ['ù+ì', 'ù+ì'],
                 ] as list($expectedValue, $inputValue)) {
            $input = $this->createMock(InputInterface::class);
            $input->expects($this->once())
                ->method('getArgument')
                ->willReturn($inputValue);

            $argument = $this->createMock(InputArgument::class);
            $argument->expects($this->once())
                ->method('getName')
                ->willReturn('argumentName');

            yield [
                $expectedValue,
                $input,
                $argument,
            ];
        }
    }

    /**
     * @dataProvider toStringProvider
     *
     * @return void
     */
    public function testToString(
        string $expectedValue,
        InputInterface $input,
        InputArgument $argument
    ) {
        $this->assertEquals(
            $expectedValue,
            Argument::toString($input, $argument)
        );
    }
}
