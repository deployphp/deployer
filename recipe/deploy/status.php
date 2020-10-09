<?php

namespace Deployer;

use Deployer\Support\Csv;
use Symfony\Component\Console\Helper\Table;

task('status', function () {
    $metainfo = Csv::parse(run('cat {{deploy_path}}/.dep/releases'));
    $releasesList = get('releases_list');
    $currentRelease = run('basename `realpath {{current_path}}`');

    $metainfo = array_filter($metainfo, function ($r) use ($releasesList) {
        return in_array($r[1], $releasesList, true);
    });
    foreach ($metainfo as &$r) {
        $r[0] = \DateTime::createFromFormat("YmdHis", $r[0])->format("Y-m-d H:i:s");
        if ($r[1] === $currentRelease) {
            $r[1] = "<info>$r[1]</info>";
        }
     }

    $table = new Table(output());
    $table
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(['Date', 'Release', 'Author', 'Target'])
        ->setRows($metainfo);
    $table->render();
});
