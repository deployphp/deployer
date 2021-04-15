<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class BlackjackCommand extends Command
{
    use CommandCommon;

    /**
     * @var Input
     */
    private $input;

    /**
     * @var Output
     */
    private $output;

    public function __construct()
    {
        parent::__construct('play:blackjack');
        $this->setDescription('Play blackjack');
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->telemetry();
        $io = new SymfonyStyle($input, $output);

        $this->print("╭─────────────────────╮");
        $this->print("│                     │");
        $this->print("│      Welcome!       │");
        $this->print("│                     │");
        $this->print("╰─────────────────────╯");

        $money = 100;
        $hasWatch = true;
        $orderWhiskey = false;
        $whiskeyLevel = 0;

        $deck = $this->newDeck();
        $graveyard = [];
        $dealersHand = [];
        $playersHand = [];
        shuffle($deck);
        $deal = function () use (&$deck, &$graveyard) {
            if (count($deck) == 0) {
                shuffle($graveyard);
                $deck = $graveyard;
                $graveyard = [];
            }
            return array_pop($deck);
        };

        start:
        $this->print("You have <info>$</info><info>$money</info>.");
        if ($money > 0) {
            $bet = (int)$io->ask('Your bet', '5');
            if ($bet <= 0) goto start;
            if ($bet > $money) goto start;
        } else if ($hasWatch) {
            $answer = $io->askQuestion(new ChoiceQuestion('?', ['leave', '- Here, take my watch! [$25]'], 0));
            if ($answer == 'leave') {
                goto leave;
            } else {
                $hasWatch = false;
                $money = 25;
                $bet = 25;
            }
        } else {
            goto leave;
        }

        $graveyard = array_merge($graveyard, $dealersHand);
        $dealersHand = [];
        $dealersHand[] = $deal();
        $this->print("Dealers hand:");
        $this->printHand($dealersHand);

        $graveyard = array_merge($graveyard, $playersHand);
        $playersHand = [];
        $playersHand[] = $deal();
        $playersHand[] = $deal();
        $this->print("Your hand:");
        $this->printHand($playersHand, 2);

        while (true) {
            $question = new ChoiceQuestion('Your turn', ['hit', 'stand'], 0);
            $answer = $io->askQuestion($question);

            if ($answer === 'hit') {
                $playersHand[] = $deal();
                usleep(200000);
            }

            if ($answer === 'stand') {
                break;
            }

            $this->printHand($playersHand);
            $handValue = $this->handValue($playersHand);

            if ($handValue > 21) {
                $this->print("You got <comment>$handValue</comment>.");
                $this->print("<fg=cyan>Bust!</>");
                $this->print("-<info>$</info><info>$bet</info>");
                $money -= $bet;
                goto nextRound;
            }
        }

        $this->printHand($dealersHand);
        $this->print("Dealer: " . $this->handValue($dealersHand));
        sleep(1);

        while ($this->handValue($dealersHand) <= 17) {
            $dealersHand[] = $deal();
            $this->printHand($dealersHand);
            $this->print("Dealer: " . $this->handValue($dealersHand));
            sleep(1);
        }

        $d = $this->handValue($dealersHand);
        $p = $this->handValue($playersHand);
        $this->print("You got <comment>$p</comment> and dealer <comment>$d</comment>.");

        if ($d > 21 || $p > $d) {
            $this->print("<fg=cyan>You won!</>");
            $this->print("+<info>$</info><info>$bet</info>");
            $money += $bet;
        } else if ($p < $d) {
            $this->print("<fg=cyan>You lose!</>");
            $this->print("-<info>$</info><info>$bet</info>");
            $money -= $bet;
        } else {
            $this->print("<fg=cyan>Push!</>");
        }

        nextRound:
        $choices = ['continue', 'leave'];
        if ($orderWhiskey) {
            $orderWhiskey = false;
            $whiskeyLevel = 4;
            $this->print();
            $this->print('The waitress brought whiskey and says:');
            $this->print(' - Your whiskey, sir.');
            if ($money >= 5) {
                array_push($choices, 'tip the waitress [$5]');
            }
        } else if ($money >= 5) {
            array_push($choices, 'order whiskey [$5]');
        }

        if ($whiskeyLevel > 0) {
            $this->printWhiskey($whiskeyLevel);
            $whiskeyLevel--;
        }
        $answer = $io->askQuestion(new ChoiceQuestion('?', $choices, 0));

        if ($answer == 'leave') {
            goto leave;
        } else if ($money >= 5 && $answer == 'order whiskey [$5]') {
            $orderWhiskey = true;
            $this->print('You say:');
            $this->print(' - Whiskey, please.');
            $money -= 5;
        } else if ($money >= 5 && $answer == 'tip the waitress [$5]') {
            $this->print('The waitress says:');
            $this->print(' - Thank you, sir!');
            $money -= 5;
        }
        $this->print();
        $this->print("=====> Next round <=====");
        goto start;

        leave:
        if ($money >= 5) {
            $answer = $io->ask('Leave a $5 tip to the dealer?', 'yes');
            if ($answer === 'yes') {
                $this->print("You can leave a tip here:");
                $this->print();
                $this->print("- https://github.com/sponsors/antonmedv");
                $this->print("- https://www.patreon.com/deployer");
                $this->print("- https://paypal.me/antonmedv");
                $this->print();
            }
        }
        $this->print('Thanks for playing, Come again!');
        return 0;
    }

    private function newDeck(): array
    {
        $deck = [];
        foreach (['♠', '♣', '♥', '♦'] as $suit) {
            for ($i = 2; $i <= 10; $i++) {
                $deck[] = [strval($i), $suit];
            }
            $deck[] = ['J', $suit];
            $deck[] = ['Q', $suit];
            $deck[] = ['K', $suit];
            $deck[] = ['A', $suit];
        }
        return $deck;
    }

    private function handValue(array $hand): int
    {
        $aces = 0;
        $value = 0;
        foreach ($hand as list($rank)) {
            switch ($rank) {
                case '2':
                    $value += 2;
                    break;
                case '3':
                    $value += 3;
                    break;
                case '4':
                    $value += 4;
                    break;
                case '5':
                    $value += 5;
                    break;
                case '6':
                    $value += 6;
                    break;
                case '7':
                    $value += 7;
                    break;
                case '8':
                    $value += 8;
                    break;
                case '9':
                    $value += 9;
                    break;
                case '10':
                case 'J':
                case 'Q':
                case 'K':
                    $value += 10;
                    break;
                case 'A':
                    $aces++;
                    break;
            }
        }
        while ($aces-- > 0) {
            if ($value + 11 <= 21) {
                $value += 11;
            } else {
                $value += 1;
            }
        }
        return $value;
    }

    private function print(string $text = "")
    {
        $this->output->writeln(" $text");
    }

    private function printHand(array $hand, int $offset = 1)
    {
        $cards = [];
        for ($i = 0; $i < count($hand) - $offset; $i++) {
            list($rank) = $hand[$i];
            $cards[] = [
                "┌───",
                "│" . str_pad($rank, 3),
                "│   ",
                "│   ",
                "│   ",
                "│   ",
                "└───",
            ];
        }

        for (; $i < count($hand); $i++) {
            list($rank, $suit) = $hand[$i];
            $cards[] = [
                "┌───────┐",
                "│" . str_pad($rank, 7) . "│",
                "│       │",
                "│   " . $suit . "   │",
                "│       │",
                "│" . str_pad($rank, 7, " ", STR_PAD_LEFT) . "│",
                "└───────┘",
            ];
        }

        for ($i = 0; $i < 7; $i++) {
            $this->output->write(" ");
            foreach ($cards as $lines) {
                $this->output->write($lines[$i]);
            }
            $this->output->write("\n");
        }
    }

    private function printWhiskey(int $whiskeyLevel)
    {
        if ($whiskeyLevel == 4) {
            echo <<<ASCII

 |          |
 |__________|
 |          |
 | /\ / /\ /|
 |/_/__/__\_|


ASCII;
        }
        if ($whiskeyLevel == 3) {
            echo <<<ASCII

 |          |
 |          |
 |__________|
 | /\ / /\ /|
 |/_/__/__\_|


ASCII;
        }
        if ($whiskeyLevel == 2) {
            echo <<<ASCII

 |          |
 |          |
 |          |
 |_/\_/_/\_/|
 |/_/__/__\_|


ASCII;
        }
        if ($whiskeyLevel == 1) {
            echo <<<ASCII

 |          |
 |          |
 |          |
 | /\ / /\ /|
 |/_/__/__\_|


ASCII;
        }
    }
}
