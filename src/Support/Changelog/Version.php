<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support\Changelog;

class Version
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $previous;

    /**
     * @var Item[]
     */
    private $added;

    /**
     * @var Item[]
     */
    private $changed;

    /**
     * @var Item[]
     */
    private $fixed;

    /**
     * @var Item[]
     */
    private $removed;

    public function __toString(): string
    {
        $f = function (Item $item): string {
            return "- $item";
        };

        $added = '';
        $changed = '';
        $fixed = '';
        $removed = '';
        if (!empty($this->added)) {
            $added = sprintf("### Added\n%s\n\n", implode("\n", array_map($f, $this->added)));
        }
        if (!empty($this->changed)) {
            $changed = sprintf("### Changed\n%s\n\n", implode("\n", array_map($f, $this->changed)));
        }
        if (!empty($this->fixed)) {
            $fixed = sprintf("### Fixed\n%s\n\n", implode("\n", array_map($f, $this->fixed)));
        }
        if (!empty($this->removed)) {
            $removed = sprintf("### Removed\n%s\n\n", implode("\n", array_map($f, $this->removed)));
        }

        return <<<MD
## {$this->version}
[{$this->previous}...{$this->version}](https://github.com/deployphp/deployer/compare/{$this->previous}...{$this->version})

{$added}{$changed}{$fixed}{$removed}
MD;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version)
    {
        $this->version = $version;
    }

    public function setPrevious(string $previous)
    {
        $this->previous = $previous;
    }

    /**
     * @param Item[] $added
     */
    public function setAdded(array $added)
    {
        $this->added = $added;
    }

    /**
     * @param Item[] $changed
     */
    public function setChanged(array $changed)
    {
        $this->changed = $changed;
    }

    /**
     * @param Item[] $fixed
     */
    public function setFixed(array $fixed)
    {
        $this->fixed = $fixed;
    }

    /**
     * @param Item[] $removed
     */
    public function setRemoved(array $removed)
    {
        $this->removed = $removed;
    }

    public function addAdded(Item $added)
    {
        $this->added[] = $added;
    }

    public function addChanged(Item $changed)
    {
        $this->changed[] = $changed;
    }

    public function addFixed(Item $fixed)
    {
        $this->fixed[] = $fixed;
    }

    public function addRemoved(Item $removed)
    {
        $this->removed[] = $removed;
    }
}
