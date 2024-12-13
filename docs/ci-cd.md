# CI/CD

## GitHub Actions

Use official [GitHub Action for Deployer](https://github.com/deployphp/action).

Create `.github/workflows/deploy.yml` file with following content:

```yaml
name: deploy

on:
  push:
    branches: [master]

concurrency: production_environment

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.1"

      - name: Install dependencies
        run: composer install

      - name: Deploy
        uses: deployphp/action@v1
        with:
          private-key: ${{ secrets.PRIVATE_KEY }}
          dep: deploy
```

:::warning
The `concurrency: production_environment` is important as it prevents concurrent
deploys.
:::

## GitLab CI/CD

Set the following variables in your GitLab project:

- `SSH_KNOWN_HOSTS`: Content of `~/.ssh/known_hosts` file.
  The public SSH keys for a host may be obtained using the utility `ssh-keyscan`.
  For example: `ssh-keyscan deployer.org`.
- `SSH_PRIVATE_KEY`: Private key for connecting to remote hosts.
  To generate a private key: `ssh-keygen -t ed25519 -C 'gitlab@deployer.org'`.

Create a .gitlab-ci.yml file with the following content:

```yml
stages:
  - deploy

deploy:
  stage: deploy
  image:
    name: deployphp/deployer:v7
    entrypoint: [""]
  before_script:
    - mkdir -p ~/.ssh
    - eval $(ssh-agent -s)
    - echo "$SSH_KNOWN_HOSTS" > ~/.ssh/known_hosts
    - chmod 644 ~/.ssh/known_hosts
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add - > /dev/null
  script:
    - dep deploy -vvv
  resource_group: production
  only:
    - master
```

## Bitbucket Pipelines

Firstly, [generate a new SSH key and add it to your workspace for the server](https://support.atlassian.com/bitbucket-cloud/docs/configure-ssh-and-two-step-verification/). There are instructions on the SSH Keys page that can help you add this key to your server.

You may also need to [define your environment variables](https://support.atlassian.com/bitbucket-cloud/docs/set-up-and-monitor-deployments/#Step-1--Define-your-environments) that you need to use in your deploy commands.

Create a bitbucket-pipelines.yml file with the following content:

```yml
pipelines:
  branches:
    develop:
      - stage:
          # this is the target deployment name and it will inherit the environment from it
          deployment: staging
          name: Deploy Staging
          steps:
            - step:
              name: Composer Install
              image: composer/composer:2.2
              caches:
                - composer
              script:
                - composer install --quiet
              artifacts:
                # we need to save all these files so that they can be picked up in the actual deployment
                - vendor/**
            - step:
                name: NPM Install
                image: node:22-bullseye-slim
                caches:
                  - node
                script:
                  - npm install --silent
                artifacts:
                  # we need to save all these files so that they can be picked up in the actual deployment
                  - public/build/**
            - step:
              name: Deployer Deploy
              timeout: 6m # if it takes longer than this, error out
              # @see https://hub.docker.com/r/deployphp/deployer/tags?name=v7.5
              image: deployphp/deployer:v7.5.8
              script:
                # pass $DEVELOP and $STAGING variables from the "staging" deployment environment
                - php /bin/deployer.phar deploy --branch=$DEVELOP stage=$STAGING
```

### Deployment concurrency

Only one deployment job runs at a time with the [`resource_group` keyword](https://docs.gitlab.com/ee/ci/yaml/index.html#resource_group) in .gitlab-ci.yml.

In addition, you can ensure that older deployment jobs are cancelled automatically when a newer deployment runs by enabling the [skip outdated deployment jobs](https://docs.gitlab.com/ee/ci/pipelines/settings.html#prevent-outdated-deployment-jobs) feature (enabled by default).

### Deploy secrets

It is not recommended to commit secrets to the repository, you could use a GitLab variable to store them instead.

Many frameworks use dotenv to store secrets, let's create a GitLab file variable named `DOTENV`, so it can be deployed along with the code.

Set up a deployer task to copy secrets to the server:

```php
task('deploy:secrets', function () {
    upload(getenv('DOTENV'), '{{deploy_path}}/shared/.env');
});
```

Run the task immediately after updating the code.
