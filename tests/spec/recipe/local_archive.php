<?php

namespace Deployer;

require 'recipe/common.php';

set('update_code_strategy', 'local_archive');
set('deploy_path', sys_get_temp_dir() . '/deployer/{{hostname}}');
set('sub_directory', 'tests/fixtures/repository');
set('keep_releases', 1);

localhost('alpha');
localhost('beta');
localhost('gamma');
