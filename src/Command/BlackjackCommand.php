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
use function Deployer\Support\array_flatten;

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
        parent::__construct('blackjack');
        $this->setDescription('Play blackjack');
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->telemetry();
        $io = new SymfonyStyle($this->input, $this->output);

        if (getenv('COLORTERM') === 'truecolor') {
            $this->print("\x1b[38;2;255;95;109m╭\x1b[39m\x1b[38;2;255;95;107m─\x1b[39m\x1b[38;2;255;96;106m─\x1b[39m\x1b[38;2;255;96;104m─\x1b[39m\x1b[38;2;255;96;103m─\x1b[39m\x1b[38;2;255;97;101m─\x1b[39m\x1b[38;2;255;97;100m─\x1b[39m\x1b[38;2;255;97;99m─\x1b[39m\x1b[38;2;255;98;97m─\x1b[39m\x1b[38;2;255;100;98m─\x1b[39m\x1b[38;2;255;102;98m─\x1b[39m\x1b[38;2;255;104;98m─\x1b[39m\x1b[38;2;255;106;99m─\x1b[39m\x1b[38;2;255;108;99m─\x1b[39m\x1b[38;2;255;110;99m─\x1b[39m\x1b[38;2;255;112;100m─\x1b[39m\x1b[38;2;255;114;100m─\x1b[39m\x1b[38;2;255;116;100m─\x1b[39m\x1b[38;2;255;118;100m─\x1b[39m\x1b[38;2;255;120;101m─\x1b[39m\x1b[38;2;255;122;101m─\x1b[39m\x1b[38;2;255;124;101m─\x1b[39m\x1b[38;2;255;126;102m╮\x1b[39m");
            $this->print("\x1b[38;2;255;128;102m│\x1b[39m                     \x1b[38;2;255;130;102m│\x1b[39m");
            $this->print("\x1b[38;2;255;132;103m│\x1b[39m      \x1b[38;2;255;134;103mW\x1b[39m\x1b[38;2;255;136;103me\x1b[39m\x1b[38;2;255;138;104ml\x1b[39m\x1b[38;2;255;140;104mc\x1b[39m\x1b[38;2;255;142;104mo\x1b[39m\x1b[38;2;255;144;104mm\x1b[39m\x1b[38;2;255;146;105me\x1b[39m\x1b[38;2;255;148;105m!\x1b[39m       \x1b[38;2;255;150;105m│\x1b[39m");
            $this->print("\x1b[38;2;255;152;106m│\x1b[39m                     \x1b[38;2;255;153;106m│\x1b[39m");
            $this->print("\x1b[38;2;255;155;106m╰\x1b[39m\x1b[38;2;255;157;107m─\x1b[39m\x1b[38;2;255;159;107m─\x1b[39m\x1b[38;2;255;161;107m─\x1b[39m\x1b[38;2;255;163;108m─\x1b[39m\x1b[38;2;255;165;108m─\x1b[39m\x1b[38;2;255;166;108m─\x1b[39m\x1b[38;2;255;168;108m─\x1b[39m\x1b[38;2;255;170;109m─\x1b[39m\x1b[38;2;255;172;109m─\x1b[39m\x1b[38;2;255;174;109m─\x1b[39m\x1b[38;2;255;176;110m─\x1b[39m\x1b[38;2;255;177;110m─\x1b[39m\x1b[38;2;255;179;110m─\x1b[39m\x1b[38;2;255;181;111m─\x1b[39m\x1b[38;2;255;183;111m─\x1b[39m\x1b[38;2;255;185;111m─\x1b[39m\x1b[38;2;255;186;111m─\x1b[39m\x1b[38;2;255;188;112m─\x1b[39m\x1b[38;2;255;190;112m─\x1b[39m\x1b[38;2;255;192;112m─\x1b[39m\x1b[38;2;255;193;113m─\x1b[39m\x1b[38;2;255;195;113m╯\x1b[0m");
        } else {
            $this->print("╭─────────────────────╮");
            $this->print("│                     │");
            $this->print("│      Welcome!       │");
            $this->print("│                     │");
            $this->print("╰─────────────────────╯");
        }

        $money = 100;

        if (md5(strval(getenv('MONEY'))) === '5a7c2f336d0cc43b68951e75cdffe333') {
            $money += 25;
            $this->print('<fg=cyan>You got an extra $25.</>');
        } else if (md5(strval(getenv('MONEY'))) === '530029252abcbda4a2a2069036ccc7fc') {
            $money += 100;
            $this->print('<fg=cyan>You got an extra $100.</>');
        } else if (md5(strval(getenv('MONEY'))) === '1aa827a06ecbfa5d6fa7c62ad245f3a3') {
            $money = 100000;
        }

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
            $handValue = self::handValue($playersHand);

            if ($handValue > 21) {
                $this->print("You got <comment>$handValue</comment>.");
                $this->print("<fg=cyan>Bust!</>");
                $this->print("-<info>$</info><info>$bet</info>");
                $money -= $bet;
                goto nextRound;
            }
        }

        $this->printHand($dealersHand);
        $this->print("Dealer: " . self::handValue($dealersHand));
        sleep(1);

        while (self::handValue($dealersHand) <= 17) {
            $dealersHand[] = $deal();
            $this->printHand($dealersHand);
            $this->print("Dealer: " . self::handValue($dealersHand));
            sleep(1);
        }

        $d = self::handValue($dealersHand);
        $p = self::handValue($playersHand);
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

    public static function handValue(array $hand): int
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
        $variants = [$value];
        while ($aces-- > 0) {
            $variants = array_flatten(array_map(function ($v) {
                return [$v + 1, $v + 11];
            }, $variants));
        }
        $sum = $variants[0];
        for ($i = 1; $i < count($variants); $i++) {
            if ($variants[$i] <= 21) {
                $sum = $variants[$i];
            } else {
                break;
            }
        }
        return $sum;
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
