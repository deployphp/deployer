<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

// TODO: Wait until Deployer 7.1 with only php8 supports.
//class NamedArgumentsTest extends AbstractTest
//{
//    const RECIPE = __DIR__ . '/recipe/named_arguments.php';
//
//    public function testRunWithNamedArguments()
//    {
//        $this->init(self::RECIPE);
//        $this->tester->run(['named_arguments', '-f' => self::RECIPE], ['verbosity' => Output::VERBOSITY_VERBOSE]);
//
//        $display = $this->tester->getDisplay();
//        self::assertEquals(0, $this->tester->getStatusCode(), $display);
//        self::assertStringContainsString('Hello, world!', $display);
//    }
//
//    public function testRunWithOptions()
//    {
//        $this->init(self::RECIPE);
//        $this->tester->run(['options', '-f' => self::RECIPE], ['verbosity' => Output::VERBOSITY_VERBOSE]);
//
//        $display = $this->tester->getDisplay();
//        self::assertEquals(0, $this->tester->getStatusCode(), $display);
//        self::assertStringContainsString('Hello, Anton!', $display);
//    }
//
//    public function testRunWithOptionsWithNamedArguments()
//    {
//        $this->init(self::RECIPE);
//        $this->tester->run(['options_with_named_arguments', '-f' => self::RECIPE], ['verbosity' => Output::VERBOSITY_VERBOSE]);
//
//        $display = $this->tester->getDisplay();
//        self::assertEquals(0, $this->tester->getStatusCode(), $display);
//        self::assertStringContainsString('Hello, override!', $display);
//    }
//
//    public function testRunLocallyWithNamedArguments()
//    {
//        $this->init(self::RECIPE);
//        $this->tester->run(['run_locally_named_arguments', '-f' => self::RECIPE], ['verbosity' => Output::VERBOSITY_VERBOSE]);
//
//        $display = $this->tester->getDisplay();
//        self::assertEquals(0, $this->tester->getStatusCode(), $display);
//        self::assertStringContainsString('Hello, world!', $display);
//    }
//}
