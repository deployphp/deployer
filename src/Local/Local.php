<?php


namespace Deployer\Local;



use Deployer\Server\ServerInterface;
use Symfony\Component\Finder\Finder;
use Deployer\Utils;

class Local implements LocalInterface
{
    /**
     * @param string $command
     * @return string
     */
    public function run($command)
    {
        $descriptors = array(
            0 => array("pipe", "r"), // stdin - read channel
            1 => array("pipe", "w"), // stdout - write channel
            2 => array("pipe", "w"), // stdout - error channel
            3 => array("pipe", "r"), // stdin
        );

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            die("Can't open resource with proc_open.");
        }

        // Nothing write to input.
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if ($error) {
            throw new \RuntimeException($error);
        }

        fclose($pipes[3]);

        // Close all pipes before proc_close!
        $code = proc_close($process);

        return $output;
    }

    /**
     * @param ServerInterface $server
     * @param $local
     * @param $remote
     * @return mixed
     */
    public function upload(ServerInterface $server, $local, $remote, $progressHelper = null)
    {
        $remote = config()->getPath() . '/' . $remote;

        if (is_file($local)) {

            writeln("Upload file <info>$local</info> to <info>$remote</info>");

            $server->upload($local, $remote);

        } elseif (is_dir($local)) {

            writeln("Upload from <info>$local</info> to <info>$remote</info>");

            $finder = new Finder();
            $files = $finder
                ->files()
                ->ignoreUnreadableDirs()
                ->ignoreVCS(true)
                ->ignoreDotFiles(false)
                ->in($local);

            if (output()->isVerbose()) {
                $progress = progressHelper($files->count());
            }

            /** @var $file \Symfony\Component\Finder\SplFileInfo */
            foreach ($files as $file) {

                $server->upload(
                    $file->getRealPath(),
                    Utils\Path::normalize($remote . '/' . $file->getRelativePathname())
                );

                if (output()->isVerbose()) {
                    $progress->advance();
                }
            }

        } else {
            throw new \RuntimeException("Uploading path '$local' does not exist.");
        }
    }
}