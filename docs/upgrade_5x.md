# Upgrade from 4.x to 5.x

1. Servers to Hosts
   
   * `server($hostname)` to `host($hostname)`, and `server($name, $hostname)` to `host($name)->hostname($hostname)`
   * `localServer($name)` to `localhost()`
   * `cluster($name, $nodes, $port)` to `hosts(...$hodes)`
   * `serverList($file)` to `inventory($file)`
   
   If you need to deploy to same server use [host aliases](https://deployer.org/docs/hosts#host-aliases):
   
   ```php
   host('domain.com/green', 'domain.com/blue')
       ->set('deploy_path', '~/{{hostname}}')
       ...
   ```
   
   Or you can define different hosts with same hostname:
   
   ```php
   host('production')
       ->hostname('domain.com')
       ->set('deploy_path', '~/production')       
       ...
       
   host('beta')
       ->hostname('domain.com')
       ->set('deploy_path', '~/beta')       
       ...       
   ```
  
2. Configuration options

   * Rename `{{server.name}}` to `{{hostname}}`
   
3. DotArray syntax

   In v5 access to nested arrays in config via dot notation was removed. 
   If you was using it, consider to move to plain config options.
   
   Refactor this:
   
   ```php
   set('a', ['b' => 1]);
   
   // ...
   
   get('a.b');
   ```
   
   To:
   
   ```php
   set('a_b', 1);
   
   // ...
   
   get('a_b');
   ```
   
4. Credentials 

   Best practice in new v5 is to omit credentials for connection in `deploy.php` and write them in `~/.ssh/config` instead.
 
   * `identityFile($publicKeyFile,, $privateKeyFile, $passPhrase)` to `identityFile($privateKeyFile)`
   * `pemFile($pemFile)` to `identityFile($pemFile)`
   * `forwardAgent()` to `forwardAgent(true)`
   
5. Tasks constraints
 
   * `onlyOn` to `onHosts`
   * `onlyOnStage` to `onStage`
