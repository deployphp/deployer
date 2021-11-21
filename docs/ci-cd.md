# CI/CD

### GitLab CI/CD

Set the following variables in GitLab project:

- `SSH_PRIVATE_KEY`: Content of `~/.ssh/known_hosts` file. 
The public SSH keys for a host may be obtained using the utility `ssh-keyscan`. 
For example: `ssh-keyscan deployer.org`.
- `SSH_KNOW_HOSTS`: Private key for connecting to remote hosts. 
To generate private key: `ssh-keygen -t ed25519 -C 'gitlab@deployer.org'`.

Create .gitlab-ci.yml file with following content:

```yml
stages:
  - deploy

deploy:
  stage: deploy
  image:
    name: debreczeniandras/deployerphp:7-beta
    entrypoint: [""]
  before_script:
    - mkdir -p ~/.ssh
    - eval $(ssh-agent -s)
    - echo "$SSH_KNOWN_HOSTS" > ~/.ssh/known_hosts
    - chmod 644 ~/.ssh/known_hosts
    - echo "${SSH_PRIVATE_KEY}" | tr -d '\r' | ssh-add - > /dev/null
  script:
    - dep deploy -vvv
  only:
    - master
```
