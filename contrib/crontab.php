<?php
/*
Recipe for adding crontab jobs.

This recipe creates a new section in the crontab file with the configured jobs.
The section is identified by the *crontab:identifier* variable, by default the application name.

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

// Set the identifier used in the crontab, application name by default
set('crontab:identifier', function () {
    return get('application', 'application');
});

// Use sudo to run crontab. When running crontab with sudo, you can use the `-u` parameter to change a crontab for a different user.
set('crontab:use_sudo', false);

desc('Sync crontab jobs');
task('crontab:sync', function () {
    $cronJobsLocal = array_map(
        fn($job) => parse($job),
        get('crontab:jobs', [])
    );

    if (count($cronJobsLocal) == 0) {
        writeln("Nothing to sync - configure crontab:jobs");
        return;
    }

    $cronJobs = getRemoteCrontab();
    $identifier = get('crontab:identifier');
    $sectionStart = "###< $identifier";
    $sectionEnd = "###> $identifier";

    // find our cronjob section
    $start = array_search($sectionStart, $cronJobs);
    $end = array_search($sectionEnd, $cronJobs);

    if ($start === false || $end === false) {
        // Move the duplicates over when first generating the section
        foreach ($cronJobs as $index => $cronJob) {
            if (in_array($cronJob, $cronJobsLocal)) {
                unset($cronJobs[$index]);
                writeln("Crontab: Found existing job in crontab, moving it to the section");
            }
        }

        // Create the section
        $cronJobs[] = $sectionStart;
        $cronJobs = [...$cronJobs, ...$cronJobsLocal];
        $cronJobs[] = $sectionEnd;
        writeln("Crontab: Found no section, created the section with configured jobs");
    } else {
        // Replace the existing section
        array_splice($cronJobs, $start + 1, $end - $start - 1, $cronJobsLocal);
        writeln("Crontab: Found existing section, replaced with configured jobs");
    }

    setRemoteCrontab($cronJobs);
});

function setRemoteCrontab(array $lines): void
{
    $sudo = get('crontab:use_sudo') ? 'sudo' : '';

    $tmpCrontabPath = sprintf('/tmp/%s', uniqid('crontab_save_'));

    if (test("[ -f '$tmpCrontabPath' ]")) {
        run("unlink '$tmpCrontabPath'");
    }

    foreach ($lines as $line) {
        run("echo '" . $line . "' >> $tmpCrontabPath");
    }

    run("$sudo {{bin/crontab}} " . $tmpCrontabPath);
    run('unlink ' . $tmpCrontabPath);
}

function getRemoteCrontab(): array
{
    $sudo = get('crontab:use_sudo') ? 'sudo' : '';

    if (!test("$sudo {{bin/crontab}} -l >> /dev/null 2>&1")) {
        return [];
    }

    return explode(PHP_EOL, run("$sudo {{bin/crontab}} -l"));
}

