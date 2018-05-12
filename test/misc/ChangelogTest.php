<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;

class ChangelogTest extends TestCase
{
    public function testChangelogHasReferences()
    {
        $changelog = file_get_contents(__DIR__ . '/../../CHANGELOG.md');
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

    public function testChangelogReferencesOrdered()
    {
        $changelog = file_get_contents(__DIR__ . '/../../CHANGELOG.md');
        preg_match_all('|\[#(\d+)\]: https://github.com/deployphp/[\S/]+/\1/?|', $changelog, $matches);
        $refs = $matches[1];

        for ($i = 1; $i < count($refs); $i++) {
            self::assertTrue($refs[$i - 1] > $refs[$i],
                "Please, sort references in descending order.\n" .
                "References for [#{$refs[$i - 1]}] and [#{$refs[$i]}] unordered."
            );
        }
    }
}
