<?php
/*
### Installing

```php
// deploy.php

require 'recipe/rabbit.php';
```

### Configuration options

- **rabbit** *(required)*: accepts an *array* with the connection information to [rabbitmq](http://www.rabbitmq.com) server token and team name.


You can provide also other configuration options:

 - *host* - default is localhost
 - *port* - default is 5672
 - *username* - default is *guest*
 - *password* - default is *guest*
 - *channel* - no default value, need to be specified via config
 - *message* - default is **Deployment to '{$host}' on *{$prod}* was successful\n($releasePath)**
 - *vhost* - default is


```php
// deploy.php

set('rabbit', [
    'host'     => 'localhost',
    'port'     => '5672',
    'username' => 'guest',
    'password' => 'guest',
    'channel'  => 'notify-channel',
    'vhost'    => '/my-app'
]);
```

### Suggested Usage

Since you should only notify RabbitMQ channel of a successful deployment, the `deploy:rabbit` task should be executed right at the end.

```php
// deploy.php

before('deploy:end', 'deploy:rabbit');
```
 */
namespace Deployer;

use Deployer\Task\Context;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;


desc('Notifies RabbitMQ channel about deployment');
task('deploy:rabbit', function () {

    if (!class_exists('PhpAmqpLib\Connection\AMQPConnection')) {
        throw new \RuntimeException("<comment>Please install php package</comment> <info>videlalvaro/php-amqplib</info> <comment>to use rabbitmq</comment>");
    }

    $config = get('rabbit', []);

    if (!isset($config['message'])) {
        $releasePath = get('release_path');
        $host = Context::get()->getHost();

        $stage = get('stage', false);
        $stageInfo = ($stage) ? sprintf(' on *%s*', $stage) : '';

        $message = "Deployment to '%s'%s was successful\n(%s)";
        $config['message'] = sprintf(
            $message,
            $host->getHostname(),
            $stageInfo,
            $releasePath
        );
    }

    $defaultConfig = array(
        'host' => 'localhost',
        'port' => 5672,
        'username' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
    );

    $config = array_merge($defaultConfig, $config);

    if (!is_array($config) ||
        !isset($config['channel']) ||
        !isset($config['host']) ||
        !isset($config['port']) ||
        !isset($config['username']) ||
        !isset($config['password']) ||
        !isset($config['vhost']) )
    {
        throw new \RuntimeException("<comment>Please configure rabbit config:</comment> <info>set('rabbit', array('channel' => 'channel', 'host' => 'host', 'port' => 'port', 'username' => 'username', 'password' => 'password'));</info>");
    }

    $connection = new AMQPConnection($config['host'], $config['port'], $config['username'], $config['password'], $config['vhost']);
    $channel = $connection->channel();

    $msg = new AMQPMessage($config['message']);
    $channel->basic_publish($msg, $config['channel'], $config['channel']);

    $channel->close();
    $connection->close();

});
