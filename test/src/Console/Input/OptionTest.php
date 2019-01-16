<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Input;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class OptionTest extends TestCase
{
    public function toStringProvider(): \Generator
    {
        // InputOption::VALUE_NONE
        foreach ([
                    'VALUE_NONE 1' => ['--fooBar', 'fooBar', true],
                    'VALUE_NONE 2' => ['--0', '0', true],
                    'VALUE_NONE 3' => ['--1', '1', true],
                    'VALUE_NONE 4' => ['--foo\-%&Bar', 'foo\-%&Bar', true],
                    'VALUE_NONE 5' => ['--ù+ì', 'ù+ì', true],
                    'VALUE_NONE 6' => ['', 'value-none-unset', false],
                 ] as $key => list($expectedValue, $optionName, $optionValue)) {
            $input = $this->createMock(InputInterface::class);
            $input->expects($this->once())
                ->method('getOption')
                ->willReturn($optionValue);

            $option = $this->createMock(InputOption::class);
            $option->expects($this->once())
                ->method('getName')
                ->willReturn($optionName);

            $option->expects($this->once())
                ->method('acceptValue')
                ->willReturn(\false);

            yield $key => [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_REQUIRED
        foreach ([
                    'VALUE_REQUIRED 1' => ['--fooBar=ciao', 'fooBar', 'ciao'],
                    'VALUE_REQUIRED 2' => ['', 'fooBar', \null],
                    'VALUE_REQUIRED 3' => ['--fooBar=', 'fooBar', ''],
                    'VALUE_REQUIRED 4' => ['--fooBar=0', 'fooBar', '0'],
                    'VALUE_REQUIRED 5' => ['--foo\-%&Bar=test', 'foo\-%&Bar', 'test'],
                    'VALUE_REQUIRED 6' => ['--ù+ì=omg', 'ù+ì', 'omg'],
                 ] as $key => list($expectedValue, $optionName, $optionValue)) {
            $input = $this->createMock(InputInterface::class);
            $input->expects($this->once())
                ->method('getOption')
                ->willReturn($optionValue);

            $option = $this->createMock(InputOption::class);
            $option->expects($this->once())
                ->method('getName')
                ->willReturn($optionName);

            $option->expects($this->once())
                ->method('acceptValue')
                ->willReturn(\true);

            $option->expects($this->once())
                ->method('isArray')
                ->willReturn(\false);

            $option->expects($this->once())
                ->method('isValueRequired')
                ->willReturn(\true);

            yield $key => [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_OPTIONAL
        foreach ([
                    'VALUE_OPTIONAL 1' => ['--fooBar=ciao', 'fooBar', 'ciao'],
                    'VALUE_OPTIONAL 2' => ['--fooBar', 'fooBar', \null],
                    'VALUE_OPTIONAL 3' => ['--fooBar=', 'fooBar', ''],
                    'VALUE_OPTIONAL 4' => ['--fooBar=0', 'fooBar', '0'],
                    'VALUE_OPTIONAL 5' => ['--foo\-%&Bar=test', 'foo\-%&Bar', 'test'],
                    'VALUE_OPTIONAL 6' => ['--ù+ì=omg', 'ù+ì', 'omg'],
                 ] as $key => list($expectedValue, $optionName, $optionValue)) {
            $input = $this->createMock(InputInterface::class);
            $input->expects($this->once())
                ->method('getOption')
                ->willReturn($optionValue);

            $option = $this->createMock(InputOption::class);
            $option->expects($this->once())
                ->method('getName')
                ->willReturn($optionName);

            $option->expects($this->once())
                ->method('acceptValue')
                ->willReturn(\true);

            $option->expects($this->once())
                ->method('isArray')
                ->willReturn(\false);

            $option->expects($this->once())
                ->method('isValueRequired')
                ->willReturn(\false);

            yield $key => [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY
        foreach ([
                    'VALUE_ARRAY_REQUIRED 1' => ['--fooBar=ciao --fooBar=Привет', 'fooBar', ['ciao', 'Привет']],
                    'VALUE_ARRAY_REQUIRED 2' => ['--fooBar=ciao --fooBar=Привет', 'fooBar', ['ciao', \null, 'Привет']],
                    'VALUE_ARRAY_REQUIRED 3' => ['--fooBar=', 'fooBar', [\null, '']],
                    'VALUE_ARRAY_REQUIRED 4' => ['', 'fooBar', [\null]],
                    'VALUE_ARRAY_REQUIRED 5' => ['--fooBar=', 'fooBar', ['']],
                    'VALUE_ARRAY_REQUIRED 6' => ['--fooBar=0 --fooBar=1 --fooBar=2 --fooBar=...', 'fooBar', ['0', '1', '2', '...']],
                    'VALUE_ARRAY_REQUIRED 7' => ['--fooBar=ciao --fooBar= --fooBar=Привет', 'fooBar', ['ciao', '', 'Привет']],
                 ] as $key => list($expectedValue, $optionName, $optionValue)) {
            $input = $this->createMock(InputInterface::class);
            $input->expects($this->once())
                ->method('getOption')
                ->willReturn($optionValue);

            $option = $this->createMock(InputOption::class);
            $option->expects($this->once())
                ->method('getName')
                ->willReturn($optionName);

            $option->expects($this->once())
                ->method('acceptValue')
                ->willReturn(\true);

            $option->expects($this->once())
                ->method('isArray')
                ->willReturn(\true);

            $option->expects($this->once())
                ->method('isValueRequired')
                ->willReturn(\true);

            yield $key => [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY
        foreach ([
                    'VALUE_ARRAY_OPTIONAL 1' => ['--fooBar=ciao --fooBar=Привет', 'fooBar', ['ciao', 'Привет']],
                    'VALUE_ARRAY_OPTIONAL 2' => ['--fooBar=ciao --fooBar --fooBar=Привет', 'fooBar', ['ciao', \null, 'Привет']],
                    'VALUE_ARRAY_OPTIONAL 3' => ['--fooBar --fooBar=', 'fooBar', [\null, '']],
                    'VALUE_ARRAY_OPTIONAL 4' => ['--fooBar', 'fooBar', [\null]],
                    'VALUE_ARRAY_OPTIONAL 5' => ['--fooBar=', 'fooBar', ['']],
                    'VALUE_ARRAY_OPTIONAL 6' => ['--fooBar=0 --fooBar=1 --fooBar=2 --fooBar=...', 'fooBar', ['0', '1', '2', '...']],
                 ] as $key => list($expectedValue, $optionName, $optionValue)) {
            $input = $this->createMock(InputInterface::class);
            $input->expects($this->once())
                ->method('getOption')
                ->willReturn($optionValue);

            $option = $this->createMock(InputOption::class);
            $option->expects($this->once())
                ->method('getName')
                ->willReturn($optionName);

            $option->expects($this->once())
                ->method('acceptValue')
                ->willReturn(\true);

            $option->expects($this->once())
                ->method('isArray')
                ->willReturn(\true);

            $option->expects($this->once())
                ->method('isValueRequired')
                ->willReturn(\false);

            yield $key => [
                $expectedValue,
                $input,
                $option,
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
        InputOption $option
    ) {
        $this->assertEquals(
            $expectedValue,
            Option::toString($input, $option)
        );
    }
}
