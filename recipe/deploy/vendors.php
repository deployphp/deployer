<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Installing vendors');
task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        writeln('<comment>To speed up composer installation setup "unzip" command with PHP zip extension https://goo.gl/sxzFcD</comment>');
    }

    $options = trim(get('composer_options'));
    $action  = trim(get('composer_action'));
    if (strpos($options, $action) === 0) {
        set('composer_options', substr_replace($options, '', 0, strlen($action)));
    }

    run('cd {{release_path}} && {{bin/composer}} {{composer_action}} {{composer_options}}');
});
