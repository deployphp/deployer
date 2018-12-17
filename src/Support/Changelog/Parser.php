<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class Parser
{
    /**
     * @var string[]
     */
    private $tokens;

    /**
     * @var string[]
     */
    private $span;

    /**
     * @var int
     */
    private $lineNumber = 0;

    /**
     * @var bool
     */
    private $strict;

    public function __construct(string $changelog, bool $strict = true)
    {
        $this->tokens = array_map('trim', explode("\n", $changelog));
        $this->strict = $strict;
    }

    private function current(): string
    {
        if (count($this->tokens) === 0) {
            return '';
        }
        return $this->tokens[0];
    }

    private function next(): string
    {
        if (count($this->tokens) === 0) {
            throw $this->error('Unexpected end of file');
        }

        $n = ++$this->lineNumber;
        $line = array_shift($this->tokens);

        $this->span[] = "    {$n}: $line";

        if (count($this->span) > 4) {
            array_shift($this->span);
        }

        return $line;
    }

    private function acceptEmptyLine()
    {
        if ($this->strict) {
            if ('' !== $this->next()) {
                throw $this->error('Expected an empty line');
            }
        } else {
            while (preg_match('/^\s*$/', $this->current()) && count($this->tokens) > 0) {
                $this->next();
            }
        }
    }

    private function acceptEof()
    {
        if (count($this->tokens) !== 0) {
            $this->next();
            throw $this->error('Expected EOF');
        }
    }

    private function matchVersion($line, &$m = null)
    {
        return preg_match('/^\#\# \s ( v\d+\.\d+\.\d+(-[\w\.]+)? | master )$/x', $line, $m);
    }

    private function error($message): ParseException
    {
        $c = count($this->span) - 1;
        $this->span[$c] = preg_replace('/^\s{4}/', ' -> ', $this->span[$c]);

        if (count($this->tokens) > 0) {
            $this->next();
        }

        return new ParseException($message, implode("\n", $this->span));
    }

    public function parse(): Changelog
    {
        $changelog = $this->parseTitle();

        $this->acceptEmptyLine();
        $this->acceptEmptyLine();

        while ($this->matchVersion($this->current())) {
            $version = $this->parseVersion();
            $changelog->addVersion($version);
        }

        $refs = $this->parseReferences();
        $changelog->setReferences($refs);

        $this->acceptEmptyLine();
        $this->acceptEof();

        return $changelog;
    }

    private function parseTitle(): Changelog
    {
        if (preg_match('/# (.+)/', $this->next(), $m)) {
            $c = new Changelog();
            $c->setTitle($m[1]);
            return $c;
        }

        throw $this->error('Expected title');
    }

    private function parseVersion(): Version
    {
        if ($this->matchVersion($this->next(), $m)) {
            $version = new Version();
            $version->setVersion($curr = $m[1]);

            $compareLink = $this->next();
            if (!preg_match('/^\[/', $compareLink)) {
                throw $this->error('Expected link to compare page with previous version');
            }

            $prev = 'v\d+\.\d+\.\d+(-[\d\w\.]+)?';
            $regexp = "/
                ^ \[($prev)\.\.\.$curr\]
                \(https\:\/\/github\.com\/deployphp\/deployer\/compare\/$prev\.\.\.$curr\) $
                /x";

            if (preg_match($regexp, $compareLink, $m)) {
                $version->setPrevious($m[1]);
            } else {
                throw $this->error('Error in compare link syntax');
            }

            $this->acceptEmptyLine();

            $sections = ['Added', 'Changed', 'Fixed', 'Removed'];
            $sectionsCount = count($sections);

            for ($i = 0; $i < $sectionsCount; $i++) {
                foreach ($sections as $key => $section) {
                    if (preg_match('/^\#\#\# \s ' . $section . ' $/x', $this->current())) {
                        $this->next();

                        $version->{"set$section"}($this->parseItems());
                        unset($sections[$key]);

                        $this->acceptEmptyLine();

                        break;
                    }
                }
            }

            $this->acceptEmptyLine();

            return $version;
        }

        throw $this->error('Expected version');
    }

    private function parseItems(): array
    {
        $items = [];
        while (preg_match('/^\- (.+) $/x', $this->current(), $m)) {
            $this->next();

            $item = new Item();
            $message = $m[1];
            $ref = '/\[ \#(\d+) \]/x';

            preg_match_all($ref, $message, $matches);
            foreach ($matches[1] as $m) {
                $item->addReference($m);
            }

            $message = trim(preg_replace($ref, '', $message));
            $item->setMessage($message);
            $items[] = $item;
        }
        return $items;
    }

    private function parseReferences(): array
    {
        $refs = [];
        while (preg_match('/^\[/', $this->current())) {
            if (preg_match('/^ \[\#(\d+)\]\: \s (https\:\/\/github\.com\/deployphp\/deployer\/(issues|pull)\/\d+)$/x', $this->next(), $m)) {
                $refs[$m[1]] = $m[2];
            } else {
                throw $this->error('Error parsing reference');
            }
        }
        return $refs;
    }
}
