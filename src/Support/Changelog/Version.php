<?php
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

    public function __toString()
    {
        $f = function (Item $item) {
            return "- $item";
        };

        $added = "";
        $changed = "";
        $fixed = "";
        $removed = "";
        if (!empty($this->added)) {
            $added = join("\n", array_map($f, $this->added));
            $added = "### Added\n$added\n\n";
        }
        if (!empty($this->changed)) {
            $changed = join("\n", array_map($f, $this->changed));
            $changed = "### Changed\n$changed\n\n";
        }
        if (!empty($this->fixed)) {
            $fixed = join("\n", array_map($f, $this->fixed));
            $fixed = "### Fixed\n$fixed\n\n";
        }
        if (!empty($this->removed)) {
            $removed = join("\n", array_map($f, $this->removed));
            $removed = "### Removed\n$removed\n\n";
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

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getPrevious(): string
    {
        return $this->previous;
    }

    public function setPrevious(string $previous): void
    {
        $this->previous = $previous;
    }

    /**
     * @return Item[]
     */
    public function getAdded(): array
    {
        return $this->added;
    }

    /**
     * @param Item[] $added
     */
    public function setAdded(array $added): void
    {
        $this->added = $added;
    }

    /**
     * @return Item[]
     */
    public function getChanged(): array
    {
        return $this->changed;
    }

    /**
     * @param Item[] $changed
     */
    public function setChanged(array $changed): void
    {
        $this->changed = $changed;
    }

    /**
     * @return Item[]
     */
    public function getFixed(): array
    {
        return $this->fixed;
    }

    /**
     * @param Item[] $fixed
     */
    public function setFixed(array $fixed): void
    {
        $this->fixed = $fixed;
    }

    /**
     * @return Item[]
     */
    public function getRemoved(): array
    {
        return $this->removed;
    }

    /**
     * @param Item[] $removed
     */
    public function setRemoved(array $removed): void
    {
        $this->removed = $removed;
    }
}
