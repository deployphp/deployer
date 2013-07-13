<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utils;

use Symfony\Component\Finder\Finder;

class Compiler
{
    protected $fromDir;

    public function compile($fromDir, $pharFile = 'deployer.phar')
    {
        $this->fromDir = realpath(rtrim($fromDir, DIRECTORY_SEPARATOR));

        if (!is_dir($this->fromDir)) {
            throw new \RuntimeException("Directory '$fromDir' does not exist.");
        }

        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, 'deployer.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('phpunit')
            ->exclude('Tests')
            ->exclude('test')
            ->exclude('bin')
            ->notName('Compiler.php')
            ->in($this->fromDir);

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo($this->fromDir . '/LICENSE'), false);

        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        echo "Phar package compiled successful.\n";

        unset($phar);
    }

    private function addFile(\Phar $phar, \SplFileInfo $file, $strip = true)
    {
        $path = str_replace($this->fromDir . DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        }

        $phar->addFromString($path, $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub()
    {
        return <<<'EOF'
<?php
require 'phar://deployer.phar/vendor/autoload.php';
deployer();
__HALT_COMPILER();
EOF;
    }
}