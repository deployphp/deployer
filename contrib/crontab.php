<?php
/*
Recipe for adding crontab jobs.

It checks for duplicates by the command part of the job. Changing the schedule will update the crontab. So when you change the command part you have to manually remove the old one. Use `crontab -e` on the server to remove it.

## Configuration

- *crontab:jobs* - An array of strings with crontab lines.

## Usage

```php
require 'contrib/crontab.php';

after('deploy:success', 'crontab:sync');

add('crontab:jobs', [
    '* * * * * cd {{current_path}} && {{bin/php}} artisan schedule:run >> /dev/null 2>&1',
]);
```
 */
namespace Deployer;

// Get path to bin
set('bin/crontab', function () {
    return which('crontab');
});

desc('Loads crontab');
task('crontab:load', function () {
    set('crontab:all', []);

    // Crontab is empty
    if (!test ("{{bin/crontab}} -l >> /dev/null 2>&1")) {
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
        $syncJob = parse($syncJob);
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

    $tmpCrontabPath = \sprintf('/tmp/%s', \uniqid('crontab_save_'));

    if (test("[ -f '$tmpCrontabPath' ]")) {
        run("unlink '$tmpCrontabPath'");
    }

    foreach ($cronJobs as $cronJob) {
        $jobString = $cronJob['minute'] . ' ' . $cronJob['hour'] . ' ' . $cronJob['day'] . ' ' . $cronJob['month'] . ' ' . $cronJob['weekday'] . ' ' . $cronJob['cmd'];
        run("echo '" . $jobString . "' >> $tmpCrontabPath");
    }

    run('{{bin/crontab}} ' . $tmpCrontabPath);
    run('unlink ' . $tmpCrontabPath);
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

