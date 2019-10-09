# Sample Real-World Deployment Script

This is a simple deployment script, based on a real-world use of Deployer. (It's based on the excellent article "[How To Automatically Deploy Your PHP Apps](https://www.codepicky.com/php-automatic-deploy/)" by Cosmin.) The goal of this script is to give a starting point and provide an overview for what a complete, fully-implemented Deployer script could look like (it is heavily commented to thoroughly document what's going on).

Note that this is not necessarily the best or only way to accomplish a deployment&mdash; not even close! Deployer has a lot of commands and options worth exploring. :-) You are encouraged to take this code and expand/improve/adapt it for your own use.

## Using the script

### Initial setup

1. Adapt this script as needed for your project: change to your paths, server (host) info, github repos, etc.
2. Save this script as `deploy.php` in the root folder of your project
3. Install Deployer on your machine, either in the project folder or globally

### Deploying

Use either `./dep` to deploy to staging, or `./dep production` for production deployment.


## Getting started

This script is ready to run "out of the box" as you see it below. However, its base was initially created by running `dep init` and using the `common` option. If you're deploying code based on a standard framework (such as Laravel, Symfony, Yii, Drupal, etc.) then you may wish to start a new script with `dep init` and choose your framework, then adapt that new script as needed.

## Sample script

```php
<?php

 /**
  * Deployer script
  * For use with Deployer tool installed on local machine (see https://deployer.org)
  *
  * Based on script by Cosmin at https://www.codepicky.com/php-automatic-deploy/
  *
  * Eric Mueller, 1 October 2019
  */

// next line created by Deployer init
namespace Deployer;

// keep track of the start time so we can compute total time
$startTime = microtime( true );

// start with Deployer "base" common recipe
require 'recipe/common.php';

// default for deployment is staging
set( 'default_stage', 'staging' );

// if we need to run commands on the target server(s) as sudo, change the next line to: set( 'sudo_cmd', 'sudo' );
set( 'sudo_cmd', '' );

// Project name
set( 'application', 'mysite' );

// keep the most recent 10 releases
set( 'keep_releases', 10 );

// get a list of all the releases as an array
set('releases_list', function () {
	return explode("\n", run('ls -dt {{deploy_path}}/releases/*'));
});

// this web site pulls from two repositories - the main repo and a secondary repo with a chat client
set( 'repo-main', 'https://github_username@github.com/github_username/my-site.git' );
set( 'repo-chat-client', 'https://github_username@github.com/github_username/my-chat-client.git' );

// allocate tty for git clone - this is if you need to enter a passphrase or whatever to
// authenticate with github. for public repos you probably won't need this, for private
// repos you will almost definitely need this. if you aren't sure, it doesn't hurt to keep
// it turned on.
set( 'git_tty', true );

// allow Deployer tool to collect anonymous statistics about usage
set( 'allow_anonymous_stats', true );

/**
 * define hosts
 */

// note that staging server uses a non-standard port for SSH so I have to specify it here
host( 'staging.example.com' )
	->user( 'eric' )
	->stage( 'staging' )
	->port( 30122 )
	->set( 'deploy_path', '/usr/share/nginx/staging.example.com' )
	->identityFile( '~/.ssh/id_rsa' );

// note that live server also uses a non-standard port for SSH
host( 'example.com' )
	->user( 'eric' )
	->stage( 'production' )
	->port( 30122 )
	->set( 'deploy_path', '/usr/share/nginx/example.com' )
	->identityFile( '~/.ssh/id_rsa' );


/**
 * define deployer tasks
 * we have broken the tasks down into discrete subtasks
 * and then at the bottom there's one task to call all the subtasks
 */

// if deploy to production, then ask to be sure
task( 'confirm', function () {
	if ( ! askConfirmation( 'Are you sure you want to deploy to production?' ) ) {
		write( 'Ok, quitting.' );
		die;
	}
} )->onStage( 'production' );

// create new release folder on server
task( 'create:release', function () {
	$i = 0;
	do {
		$releasePath = '{{deploy_path}}/releases/' . date( 'Y-m-d_Hi_' ) . $i ++;
	} while ( run( "if [ -d $releasePath ]; then echo exists; fi;" ) == 'exists' );
	run( "{{sudo_cmd}} mkdir $releasePath", ['tty' => true] );
	set( 'release_path', $releasePath );
	writeln( "Release path: $releasePath" );
} );

// check out code from main repo and put into release folder
task( 'update:code-main', function () {
	run( "{{sudo_cmd}} git clone -q --depth 1 {{repo-main}} {{release_path}}" );

	// remove a few assorted things that are in the repo but should not be on the server
	cd( "{{release_path}}" );
	run( "{{sudo_cmd}} rm -rf server-extensions" );
	run( "{{sudo_cmd}} rm -rf tests" );
	run( "{{sudo_cmd}} rm -rf README.md" );
	run( "{{sudo_cmd}} rm -rf codecept" );
	run( "{{sudo_cmd}} rm -rf codeception.yml" );
} );

// check out code from 2nd repo and move it into place
task( 'update:code-chat-client', function () {
	// note next line: for this repo, pull from the special 'deploy' branch
	run( "{{sudo_cmd}} git clone -q -b deploy --depth 1 {{repo-chat-client}} {{release_path}}/chat-temp-checkout" );

	// move the entire final/ folder into the html/ tree
	run( "{{sudo_cmd}} mv {{release_path}}/chat-temp-checkout/final {{release_path}}/html/" );

	// get rid of leftovers from the git clone that we no longer need
	run( "{{sudo_cmd}} rm -rf {{release_path}}/chat-temp-checkout" );

	// get rid of non-minified versions of files stored in repo that we don't want on live server
	run( "{{sudo_cmd}} rm -rf {{release_path}}/html/final/master.js" );
	run( "{{sudo_cmd}} rm -rf {{release_path}}/html/final/js/src" );
} );

// update filesystem permissions
task( 'update:permissions', function () {
	run( '{{sudo_cmd}} mkdir {{release_path}}/messages/cache' );
	run( '{{sudo_cmd}} chmod -R a+w {{release_path}}/messages/cache' );

// change the owner to the webserver
	run( '{{sudo_cmd}} chown -R -h nginx:nginx {{release_path}}' );
} );

// create internal symlinks in the project
task( 'create:symlinks', function () {
	// links to external storage holding video library
	run( "{{sudo_cmd}} ln -nfs /sdb/storage/videos {{release_path}}" );
} );

// update external libraries (npm, composer, etc)
task( 'update:vendors', function () {
	writeln( '<info>  Updating composer</info>' );
	cd( '{{release_path}}' );
	run( 'composer install --no-dev' );
	cd( '{{release_path}}/html/final' );
	run( 'composer install --no-dev' );

	writeln( '<info>  Updating npm</info>' );
	cd( '{{release_path}}/html' );
	run( 'npm update' );
	cd( '{{release_path}}/html/final' );
	run( 'npm update' );
} );

// change the symlinks that the webserver uses, to actually "launch" this release
task( 'update:release_symlinks', function () {
    // for each of the links below, first we we check for (and remove) any existing symlink
    // then put the new link in place
	// -e means if file exists, -h is if it is a symlink
	run( '{{sudo_cmd}} cd {{deploy_path}} && if [ -e html ]; then {{sudo_cmd}} rm html; fi' );
	run( '{{sudo_cmd}} cd {{deploy_path}} && if [ -h html ]; then {{sudo_cmd}} rm html; fi' );
	run( '{{sudo_cmd}} ln -nfs {{release_path}}/html {{deploy_path}}/html' );

	run( '{{sudo_cmd}} cd {{deploy_path}} && if [ -e messages ]; then {{sudo_cmd}} rm messages; fi' );
	run( '{{sudo_cmd}} cd {{deploy_path}} && if [ -h messages ]; then {{sudo_cmd}} rm messages; fi' );
	run( '{{sudo_cmd}} ln -nfs {{release_path}}/messages {{deploy_path}}/messages' );

	run( '{{sudo_cmd}} cd {{deploy_path}} && if [ -e vendor ]; then {{sudo_cmd}} rm vendor; fi' );
	run( '{{sudo_cmd}} cd {{deploy_path}} && if [ -h vendor ]; then {{sudo_cmd}} rm vendor; fi' );
	run( '{{sudo_cmd}} ln -nfs {{release_path}}/vendor {{deploy_path}}/vendor' );
} );

// as part of the deployment we need to restart the chat server
// you may also need to restart your web server, just to be safe
task( 'update:restart_chat_client_server', function () {
	run( '{{sudo_cmd}} pm2 stop chat-client' );
	run( '{{sudo_cmd}} pm2 start {{release_path}}/html/final/client.min.js --name chat-client' );
} );

// erase any extra old releases
task( 'cleanup', function () {
	$releases = get( 'releases_list' );
	$keep     = get( 'keep_releases' );     // how many to keep?

	// first, forget about the releases we want to keep...
	while ( $keep-- > 0 ) {
		array_shift( $releases );
	}

	// ...and delete the remaining (old) releases
	foreach ( $releases as $release ) {
		run( "{{sudo_cmd}} rm -rf $release" );
	}
} );

// finally, notify user that we're done and compute total time
task( 'notify:done', function () use ( $startTime ) {
	$seconds = intval( microtime( true ) - $startTime );
	$minutes = substr( '0' . intval( $seconds / 60 ), - 2 );
	$seconds %= 60;
	$seconds = substr( '0' . $seconds, - 2 );

	// show (and speak) notification on desktop so we know it's done!
    // note that next 2 commands are mac-specific
	shell_exec( "osascript -e 'display notification \"Total time: $minutes:$seconds\" with title \"Deploy Finished\"'" );
	shell_exec( 'say --rate 200 deployment finished' );
} );

// roll back to previous release
task( 'rollback', function () {
	$releases = get( 'releases_list' );
	if ( isset( $releases[1] ) ) {
		// if we are using laravel artisan, take down site
		// writeln(sprintf('  <error>%s</error>', run('php {{deploy_path}}/live/artisan down')));
		$releaseDir = $releases[1];
		run( "{{sudo_cmd}} ln -nfs $releaseDir {{deploy_path}}/live" );
		run( "{{sudo_cmd}} rm -rf {$releases[0]}" );
		writeln( "Rollback to `{$releases[1]}` release was successful." );
		// if we are using laravel artisan, bring site back up
		// writeln(sprintf('  <error>%s</error>', run("php {{deploy_path}}/live/artisan up")));
	} else {
		writeln( '  <comment>No more releases you can revert to.</comment>' );
	}
} );

// this task runs all the subtasks defined above
task( 'deploy', [
	'confirm',
	'create:release',
	'update:code-main',
	'update:code-chat-client',
	'update:permissions',
	'create:symlinks',
	'update:vendors',
	'update:release_symlinks',
	'update:restart_chat_client_server',
	'cleanup',
	'notify:done',
] );

// if deployment fails, automatically unlock
after( 'deploy:failed', 'deploy:unlock' );
```
