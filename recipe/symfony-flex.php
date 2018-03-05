<?php

namespace Deployer;

require_once __DIR__ . '/symfony3.php';

/**
 * Symfony Flex Configuration
 */

//No need to clear anything
set('clear_paths', []);

// Symfony shared dirs
set('shared_dirs', ['var/log', 'var/sessions']);

// Symfony writable dirs
set('writable_dirs', ['var/cache', 'var/log', 'var/sessions']);

//File for DotEnv if not using env vars
set('shared_files', ['.env']);

// Symfony web dir
set('web_dir', 'public');

// Assets
set('assets', ['public/css', 'public/images', 'public/js']);

// No need environment vars if using DotEnv
set('env', []);
