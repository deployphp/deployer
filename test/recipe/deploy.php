<?php

namespace Deployer;

require 'recipe/common.php';

set('application', 'deployer');
set('deploy_path', sys_get_temp_dir() . '/test/{{application}}');
host('medv.io');
after('deploy:failed', 'deploy:unlock');
