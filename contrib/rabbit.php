<?php
/* (c) Tomas Majer <tomasmajer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\Context;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;


desc('Notifying RabbitMQ channel about deployment');
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
            $host,
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
