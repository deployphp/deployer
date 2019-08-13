<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require 'recipe/common.php';
require __DIR__ . '/deploy.php';

// composer's generated autoloader always put it at the front of the loader stack.
spl_autoload_register(function ($className) {
}, true, true);
