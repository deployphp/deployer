<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class Changelog
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var Version[]
     */
    private $versions = [];

    /**
     * @var array
     */
    private $references = [];

    public function __toString()
    {
        $versions = join("\n", $this->versions);

        krsort($this->references, SORT_NUMERIC);

        $references = join("\n", array_map(function ($link, $ref) {
            return "[#$ref]: $link";
        }, $this->references, array_keys($this->references)));

        return <<<MD
# {$this->title}


{$versions}
{$references}

MD;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Version[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    public function addVersion(Version $version): void
    {
        $this->versions[] = $version;
    }

    /**
     * @return array
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @param array $references
     */
    public function setReferences(array $references): void
    {
        $this->references = $references;
    }
}
