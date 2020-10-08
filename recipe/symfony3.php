<?php
namespace Deployer;

require_once __DIR__ . '/symfony.php';

/**
 * Symfony 3 Configuration
 */

// Symfony shared dirs
set('shared_dirs', ['var/logs', 'var/sessions']);

// Symfony writable dirs
set('writable_dirs', ['var/cache', 'var/logs', 'var/sessions']);

// Symfony executable and variable directories
set('bin_dir', 'bin');
set('var_dir', 'var');
