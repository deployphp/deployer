<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace Deployer;

 require __DIR__.'/symfony4.php';

 set('bin/console', function () {
     return parse('{{release_path}}/vendor/bin/contao-console');
 });
