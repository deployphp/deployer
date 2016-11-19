<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Installing vendors');
task('deploy:vendors', function () {
    run('cd {{release_path}} && {{env_vars}} {{bin/composer}} {{composer_options}}');
});
