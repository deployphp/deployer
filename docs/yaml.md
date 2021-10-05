# YAML 

TODO


## General Example

To be added
## Laravel PHP Example

Here is an example `yaml` file for a Laravel PHP setup using the Laravel recipe `recipes/laravel.php` as well as PHP FPM & NPM from the `contrib` directory. The repository is a dummy one in this example, but all else is useful for most Laravel setups:

```yml
import:
  - recipe/laravel.php
  - contrib/php-fpm.php
  - contrib/npm.php

config:
  application: 'domain'
  repository: 'git@github.com:user/domain.git'
  php_fpm_version: '7.4'
  keep_releases: '10'
  shared_files: 
    - '.env'
    - '.transip_private_key'
  shared_dirs:
    - 'public/uploads'
    - 'public/published'
    - 'storage/logs'
    - 'storage/tls'
    - 'storage/app/public'
    - 'storage/app/modules'
  writable_dirs:
    - 'public/uploads'
    - 'public/published'
    - 'storage/framework/cache/data'
    - 'storage/logs'
    - 'storage/tls'
    - 'storage/app/public'
    - 'storage/app/modules'

hosts:
  prod:
    remote_user: forge
    hostname: 'domain.com'
    deploy_path: '~/{{hostname}}'
  staging:
    remote_user: forge
    hostname: 'staging.domain.com'
    deploy_path: '~/{{hostname}}'

tasks:
  deploy:
    - deploy:prepare
    - deploy:vendors
    - artisan:storage:link
    - artisan:view:cache
    - artisan:config:cache
    - artisan:migrate
    - npm:install
    - npm:run:prod
    - deploy:publish
    - php-fpm:reload
  npm:run:prod:
    script:
      - 'cd {{release_or_current_path}} && npm run prod'

after:
  deploy:
    - failed:deploy:unlock
    - artisan:horizon:terminate
```