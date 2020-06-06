<?php
/* (c) Asafov Sergey <asafov@newleaf.ru>
 * Recipe for crontab jobs deploy
 * Configuration:
    You need no specify only crontab:jobs, which must be array of strings
 */
namespace Deployer;

// Get path to bin
set('bin/crontab', function () {
    return run('which crontab');
});

desc('Load crontab');
task('crontab:load', function () {
    set('crontab:all', []);

    // Crontab is empty
    if (!test ("[ -f '/var/spool/cron/{{user}}' ]")) {
        return;
    }

    $cronData = run ("{{bin/crontab}} -l");
    $cronLines = explode (PHP_EOL, $cronData);

    $currentTasks = [];
    foreach ($cronLines as $cronLine) {
        $jobData = parseJob($cronLine);
        if (is_null ($jobData)) {
            continue;
        }

        $currentTasks[$jobData['ckey']] = $jobData;
    }

    set ('crontab:all', $currentTasks);
});

desc('Sync crontab jobs');
task('crontab:sync', function () {
    $syncJobs = get('crontab:jobs', []);

    if (count ($syncJobs) == 0) {
        writeln("Nothing to sync - configure crontab:jobs");
        return;
    }

    // Load current jobs
    invoke('crontab:load');
    $cronJobs = get('crontab:all');

    foreach ($syncJobs as $syncJob) {
        $syncJobData = parseJob($syncJob);

        if (is_null ($syncJobData)) {
            continue;
        }

        $cronJobData = $cronJobs[$syncJobData['ckey']] ?? NULL;

        if (!is_null ($cronJobData) && $cronJobData['skey'] == $syncJobData['skey']) {
            // Job is exists and correct
            writeLn($syncJobData['cmd'] . ': <fg=green;options=bold>OK</>');
        }
        else {
            if (is_null ($cronJobData)) {
                writeLn($syncJobData['cmd'] . ': <fg=yellow;options=bold>NEW</>');
            }
            else {
                writeLn($syncJobData['cmd'] . ': <fg=red;options=bold>FIX</>');
            }

            $cronJobs[$syncJobData['ckey']] = $syncJobData;
        }
    }

    if (test ("[ -f '/tmp/crontab_save' ]")) {
        run ("unlink '/tmp/crontab_save'");
    }

    foreach ($cronJobs as $cronJob) {
        $jobString = $cronJob['minute'] . ' ' . $cronJob['hour'] . ' ' . $cronJob['day'] . ' ' . $cronJob['month'] . ' ' . $cronJob['weekday'] . ' ' . $cronJob['cmd'];
        run ("echo '" . $jobString . "' >> '/tmp/crontab_save'");
    }

    run ("{{bin/crontab}} /tmp/crontab_save");
    run ('unlink /tmp/crontab_save');
});


function parseJob ($job) {
    if (!is_string($job)) {
        return NULL;
    }

    if (substr ($job, 0, 1) == '#') {
        return NULL;
    }

    $jobData = explode (' ', $job, 6);

    if (count ($jobData) != 6) {
        return NULL;
    }

    return [
        'skey' => md5 ($job),
        'ckey' => md5 ($jobData['5']),
        'minute' => $jobData['0'],
        'hour' => $jobData['1'],
        'day' => $jobData['2'],
        'month' => $jobData['3'],
        'weekday' => $jobData['4'],
        'cmd' => $jobData['5'],
    ];
}

