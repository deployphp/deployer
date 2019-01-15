<?php

declare(strict_types=1);
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
                     ['--fooBar', 'fooBar', true],
                     ['--0', '0', true],
                     ['--1', '1', true],
                     ['--foo\-%&Bar', 'foo\-%&Bar', true],
                     ['--ù+ì', 'ù+ì', true],
                     ['', 'value-none-unset', false],
                 ] as list($expectedValue, $optionName, $optionValue)) {
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

            yield [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_REQUIRED
        foreach ([
                     ['--fooBar=ciao', 'fooBar', 'ciao'],
                     ['', 'fooBar', \null],
                     ['', 'fooBar', ''],
                     ['--fooBar=0', 'fooBar', '0'],
                     ['--foo\-%&Bar=test', 'foo\-%&Bar', 'test'],
                     ['--ù+ì=omg', 'ù+ì', 'omg'],
                 ] as list($expectedValue, $optionName, $optionValue)) {
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

            $option->expects($this->any())
                ->method('isValueOptional')
                ->willReturn(\false);

            yield [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_OPTIONAL
        foreach ([
                     ['--fooBar=ciao', 'fooBar', 'ciao'],
                     ['--fooBar', 'fooBar', \null],
                     ['--fooBar', 'fooBar', ''],
                     ['--fooBar=0', 'fooBar', '0'],
                     ['--foo\-%&Bar=test', 'foo\-%&Bar', 'test'],
                     ['--ù+ì=omg', 'ù+ì', 'omg'],
                 ] as list($expectedValue, $optionName, $optionValue)) {
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

            $option->expects($this->any())
                ->method('isValueOptional')
                ->willReturn(\true);

            yield [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY
        foreach ([
                     ['--fooBar=ciao --fooBar=Привет', 'fooBar', ['ciao', 'Привет']],
                     ['--fooBar=ciao --fooBar=Привет', 'fooBar', ['ciao', \null, 'Привет']],
                     ['', 'fooBar', [\null, '']],
                     ['', 'fooBar', [\null]],
                     ['', 'fooBar', ['']],
                     ['--fooBar=0 --fooBar=1 --fooBar=2 --fooBar=...', 'fooBar', ['0', '1', '2', '...']],
                 ] as list($expectedValue, $optionName, $optionValue)) {
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

            $option->expects($this->any())
                ->method('isValueOptional')
                ->willReturn(\false);

            yield [
                $expectedValue,
                $input,
                $option,
            ];
        }

        // InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY
        foreach ([
                     ['--fooBar=ciao --fooBar=Привет', 'fooBar', ['ciao', 'Привет']],
                     ['--fooBar=ciao --fooBar --fooBar=Привет', 'fooBar', ['ciao', \null, 'Привет']],
                     ['--fooBar --fooBar', 'fooBar', [\null, '']],
                     ['--fooBar', 'fooBar', [\null]],
                     ['--fooBar', 'fooBar', ['']],
                     ['--fooBar=0 --fooBar=1 --fooBar=2 --fooBar=...', 'fooBar', ['0', '1', '2', '...']],
                 ] as list($expectedValue, $optionName, $optionValue)) {
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

            $option->expects($this->any())
                ->method('isValueOptional')
                ->willReturn(\true);

            yield [
                $expectedValue,
                $input,
                $option,
            ];
        }
    }

    /**
     * @dataProvider toStringProvider
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
