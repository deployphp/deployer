<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Utility\Httpie;

/* ----------------- Helper Functions ----------------- */

function debug($variable): void
{
    writeln(var_export($variable, true));
}

function debugEnabled(): bool
{
    return get('debug', false);
}

/**
 * Copy of which but ran locally
 *
 * @param string $name
 * @return void
 */
function whichLocal(string $name): string
{
    $nameEscaped = escapeshellarg($name);

    // Try `command`, should cover all Bourne-like shells
    // Try `which`, should cover most other cases
    // Fallback to `type` command, if the rest fails
    $path = runLocally("command -v $nameEscaped || which $nameEscaped || type -p $nameEscaped");
    if (empty($path)) {
        throw new \RuntimeException("Can't locate [$nameEscaped] - neither of [command|which|type] commands are available");
    }

    // Deal with issue when `type -p` outputs something like `type -ap` in some implementations
    return trim(str_replace("$name is", "", $path));
}

/**
 * Copy of which but ran contextually (local or remote)
 *
 * @param string $name
 * @return void
 */
function whichContextual(string $name, bool $local = true): string
{
    return ($local) ? whichLocal($name) : which($name);
}

function hostFromAlias(string $alias): Host
{
    $hosts = Deployer::get()->hosts;
    foreach ($hosts as $host) {
        $hostAlias = $host->getAlias();
        if (trim(strtolower($hostAlias)) == trim(strtolower($alias))) {
            return $host;
        }
    }
    throw new \RuntimeException("$alias alias is not defined");
}

function hostLocalhost(): Host
{
    $hosts = Deployer::get()->hosts;
    foreach ($hosts as $host) {
        if ($host instanceof Localhost) {
            return $host;
        }
    }
    throw new \RuntimeException("Localhost is not defined");
}

function hostHasLabel(Host $host, string $label): bool
{
    $labels = $host->getLabels();
    return (isset($labels[$label])) ? true : false;
}

function slack(string $template, $success = false): void
{
    if (!get('slack_webhook', false)) {
        return;
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_message', $template), // fake key just to allow for token replacement
        'color' => ($success) ? get('slack_success_color') : get('slack_color'),
        'mrkdwn_in' => ['text'],
    ];

    if (get('debug', false)) {
        writeln(var_export($attachment, true));
        return;
    }

    $result = Httpie::post(get('slack_webhook'))->jsonBody(['attachments' => [$attachment]])->send();
    checkSlackAnswer($result);
}
