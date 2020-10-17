<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Support\Changelog\Changelog;
use Deployer\Support\Changelog\Parser;
use PHPUnit\Framework\TestCase;

class ChangelogTest extends TestCase
{
    const CHANGELOG = __DIR__ . '/../../../../CHANGELOG.md';

    public function testChangelogHasReferences()
    {
        $changelog = file_get_contents(self::CHANGELOG);
        preg_match_all('|\[#(\d+)\]|', $changelog, $matches);

        foreach ($matches[1] as $link) {
            $👌 = preg_match("|\[#$link\]: https://github.com/deployphp/[\S/]+/$link/?|", $changelog);
            self::assertTrue($👌 === 1,
                "Reference for a link [#$link] doesn't found in CHANGELOG.md\n" .
                "Add the next line to end of CHANGELOG.md file:\n" .
                "\n" .
                "   [#$link]: https://github.com/deployphp/deployer/pull/$link" .
                "\n"
            );
        }
    }

    public function testReferencesHasLink()
    {
        $changelog = file_get_contents(self::CHANGELOG);
        preg_match_all('/(?<ref>\[#(?<id>\d+)]: https.+)/', $changelog, $matches);

        $missing = [];
        foreach ($matches['id'] as $i => $ref) {
            if (!preg_match("/\[#$ref][^:]/", $changelog)) {
                $missing[] = $matches['ref'][$i];
            }
        }

        if (count($missing) > 0) {
            self::fail("Next references does not have a link in CHANGELOG.md:\n" . implode("\n", $missing));
            return;
        }

        self::assertTrue(true);
    }

    public function testChangelogReferencesOrdered()
    {
        $changelog = file_get_contents(self::CHANGELOG);
        preg_match_all('|\[#(\d+)\]: https://github.com/deployphp/[\S/]+/\1/?|', $changelog, $matches);
        $refs = $matches[1];

        for ($i = 1; $i < count($refs); $i++) {
            self::assertTrue($refs[$i - 1] > $refs[$i],
                "Please, sort references in descending order.\n" .
                "References for [#{$refs[$i - 1]}] and [#{$refs[$i]}] unordered."
            );
        }
    }

    public function testChangelogParse()
    {
        $input = file_get_contents(self::CHANGELOG);

        $parser = new Parser($input);
        $changelog = $parser->parse();

        self::assertTrue($changelog instanceof Changelog);
    }

    public function testChangelogString()
    {
        $input = file_get_contents(self::CHANGELOG);

        $parser = new Parser($input, false);
        $changelog = $parser->parse();

        self::assertEquals(
            "$changelog",
            $input,
            "Please make sure what CHANGELOG.md formatted properly. Run next command:\n" .
            "\n" .
            "    php bin/changelog fix\n" .
            "\n"
        );
    }
}
