# Forked from [Deployer](https://github.com/deployphp/deployer)

Deployer is a PHP deployment tool with support for popular frameworks out of the box.

See [deployer.org](https://deployer.org) for more information and documentation.

It is a great tool for deploying PHP applications, and it is easy to use, but it was lacking a feature that I needed. So I forked it and added the feature I needed.

I will maintain this fork for my own use, but I will also share it with the community in case someone else needs the same feature.

## About the fork

This fork adds the possibility to rely on environment variables, so that you can share the same deployment script across different environments.

Here is an example of how to use it:

```yaml
config:
  application: '{{APP_NAME}}'
  release_name: '{{APP_VERSION}}'
  archive_release_name: '{{ARCHIVE_RELEASE_NAME}}'
  http_user: '{{HOST_HTTP_USER}}'
  keep_releases: 5
  writable_mode: 'chown'

  # Override composer_options to allow dev dependencies to be installed in staging environnement
  composer_options: '{{COMPOSER_OPTIONS}}'

hosts:
  staging:
    hostname: '{{STAGING_HOST}}'
    remote_user: '{{STAGING_REMOTE_USER}}'
    deploy_path: '~/{{application}}/{{alias}}'
  preprod:
    hostname: '{{PREPROD_HOST}}'
    remote_user: '{{PREPROD_REMOTE_USER}}'
    deploy_path: '~/{{application}}/{{alias}}'
  prod:
    hostname: '{{PROD_HOST}}'
    remote_user: '{{PROD_REMOTE_USER}}'
    deploy_path: '~/{{application}}/{{alias}}'

after:
  deploy:failed: 'deploy:unlock'
```

You can then run the deployment script like this:

```bash
APP_NAME=myapp APP_VERSION=1.0.0 ARCHIVE_RELEASE_NAME=myapp-1.0.0 HOST_HTTP_USER=www-data COMPOSER_OPTIONS="--no-dev" STAGING_HOST=staging.example.com STAGING_REMOTE_USER=deploy vendor/bin/dep deploy staging
```

But you can also use a `.env` file to store your environment variables:

```bash
# Use parentheses to execute the command in a subshell in order to avoid exporting variables to the current session
(set -a && source .env && set +a && php vendor/bin/dep deploy staging -vvv)
```

Or in a CI context, just ensure that the environment variables are set, and then run the deployment script:

```bash
vendor/bin/dep deploy ${REMOTE_NAME} -vvv
```

This way, you can share the same deployment script across different environments (or even for other projects), and you can also use the same script in a CI context, or in a local context.

Actually, the environment variables are used as a **fallback**, so you can still use the way defined by Deployer to define your variables.

## Features

- Automatic server **provisioning**.
- **Zero downtime** deployments.
- Ready to use recipes for **most frameworks**.
- Environment **variables**.

## Additional resources

* [GitHub Action for Deployer](https://github.com/deployphp/action)

## License
[MIT](https://github.com/deployphp/deployer/blob/master/LICENSE)
