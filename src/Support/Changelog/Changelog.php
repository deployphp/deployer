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

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function addVersion(Version $version)
    {
        $this->versions[] = $version;
    }

    public function prependVersion(Version $version)
    {
        array_unshift($this->versions, $version);
    }

    public function findMaster(): Version
    {
        foreach ($this->versions as $version) {
            if ($version->getVersion() === 'master') {
                return $version;
            }
        }

        $version = new Version();
        $version->setVersion('master');
        $version->setPrevious($this->findLatest()->getVersion());
        $this->prependVersion($version);

        return $version;
    }

    public function findLatest(): Version
    {
        foreach ($this->versions as $version) {
            if ($version->getVersion() === 'master') {
                continue;
            }
            return $version;
        }
        throw new \RuntimeException('There no versions.');
    }

    /**
     * @param array $references
     */
    public function setReferences(array $references)
    {
        $this->references = $references;
    }

    public function addReferences(int $ref, string $url)
    {
        $this->references[$ref] = $url;
    }
}
