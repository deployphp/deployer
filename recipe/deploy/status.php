<?php

namespace Deployer;

use Symfony\Component\Console\Helper\Table;

desc('Show releases status');
task('status', function () {
    cd('{{deploy_path}}');

    $metainfo = get('releases_metainfo');
    $currentRelease = basename(run('readlink {{current_path}}'));
    $releasesList = get('releases_list');

    foreach ($metainfo as &$r) {
        $r[0] = \DateTime::createFromFormat("YmdHis", $r[0])->format("Y-m-d H:i:s");
        $release = $r[1];
        if (in_array($release, $releasesList, true)) {
            // Add git commit rev.
            try {
                $r[] = run("cd releases/$release && git show --format='%h' --no-patch");
            } catch (\Throwable $e) {
                $r[] = '?';
            }

            if (test("[ -f releases/$release/BAD_RELEASE ]")) {
                $r[1] = "<error>$release</error> (bad)";
            } else {
                $r[1] = "<info>$release</info>";
            }
        }
        if ($release === $currentRelease) {
            $r[1] .= ' (current)';
        }
    }

    $table = new Table(output());
    $table
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(['Date', 'Release', 'Author', 'Target', 'Commit'])
        ->setRows($metainfo);
    $table->render();
});
