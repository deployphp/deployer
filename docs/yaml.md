# YAML

Deployer supports recipes written in YAML. For validating structure, Deployer uses
JSON Schema declared in [schema.json](https://github.com/deployphp/deployer/blob/master/src/schema.json).

Here is an example of YAML recipe:

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

YAML recipes can include recipes written in PHP. For example, some tasks maybe written in PHP and imported in YAML.

Also, other way around is possible: import YAML recipe from PHP. Use [import()](api.md#import) function for that.
