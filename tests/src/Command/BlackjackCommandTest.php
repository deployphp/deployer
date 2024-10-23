<?php

use Deployer\Command\BlackjackCommand;
use PHPUnit\Framework\TestCase;

class BlackjackCommandTest extends TestCase
{
    public function testHandValue()
    {
        self::assertEquals(3, BlackjackCommand::handValue([['3']]));
        self::assertEquals(10, BlackjackCommand::handValue([['3'], ['7']]));
        self::assertEquals(12, BlackjackCommand::handValue([['A'], ['A'], ['3'], ['7']]));
        self::assertEquals(12, BlackjackCommand::handValue([['A'], ['A']]));
        self::assertEquals(18, BlackjackCommand::handValue([['A'], ['7']]));
        self::assertEquals(21, BlackjackCommand::handValue([['A'], ['3'], ['7']]));
        self::assertEquals(21, BlackjackCommand::handValue([['A'], ['Q']]));
        self::assertEquals(22, BlackjackCommand::handValue([['A'], ['Q'], ['A'], ['Q']]));
    }
}
