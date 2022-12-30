# YAML

Deployer supports recipes written in YAML. For validating the structure, Deployer uses
the JSON Schema declared in [schema.json](https://github.com/deployphp/deployer/blob/master/src/schema.json).

Here is an example of a YAML recipe:

```yaml
import:
  - recipe/laravel.php

config:
  repository: "git@github.com:example/example.com.git"
  remote_user: deployer

hosts:
  example.com:
    deploy_path: "~/example"

tasks:
  build:
    - cd: "{{release_path}}"
    - run: "npm run build"

after:
  deploy:failed: deploy:unlock
```

If `release_path` is not set in php using the set() function, it can be done by setting it as an environnement variable:

``release_path=/var/foo/bar vendor/bin/dep deploy``

or by loading an .env file where this variable has been defined:

``env $(cat ./.env.local | xargs) vendor/bin/dep deploy``

or by setting it using your CI/CD tool.

YAML recipes can include recipes written in PHP. For example, some tasks maybe written in PHP and imported into YAML.

Conversely, it's also possible to import a YAML recipe from PHP using the [import()](api.md#import) function.
