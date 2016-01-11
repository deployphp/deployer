<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/symfony.php';

/**
 * Symfony 3 Configuration
 */

// Symfony shared dirs
set('shared_dirs', ['var/logs']);

// Symfony writable dirs
set('writable_dirs', ['var/cache', 'var/logs']);

set('bin_dir', 'bin');
set('var_dir', 'var');
