<?php

declare(strict_types=1);

namespace Deployer\Import;

use Deployer\Deployer;
use Deployer\Exception\SchemaException;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class MamlRecipeTest extends TestCase
{
    private Deployer $deployer;
    private string $tmpFile;

    public function setUp(): void
    {
        $console = new Application();
        $input = $this->createStub(Input::class);
        $output = $this->createStub(Output::class);

        $this->deployer = new Deployer($console);
        $this->deployer['input'] = $input;
        $this->deployer['output'] = $output;

        $this->tmpFile = tempnam(sys_get_temp_dir(), 'maml_test_') . '.maml';
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        unset($this->deployer);
    }

    private function runMaml(string $content): void
    {
        file_put_contents($this->tmpFile, $content);
        $recipe = new MamlRecipe($this->tmpFile);
        $recipe->run();
    }

    public function testConfig(): void
    {
        $this->runMaml('{
            config: {
                repository: "git@github.com:example/repo.git"
                branch: "main"
            }
        }');

        self::assertEquals('git@github.com:example/repo.git', $this->deployer->config->get('repository'));
        self::assertEquals('main', $this->deployer->config->get('branch'));
    }

    public function testConfigWithNumericValue(): void
    {
        $this->runMaml('{
            config: {
                keep_releases: 5
            }
        }');

        self::assertEquals(5, $this->deployer->config->get('keep_releases'));
    }

    public function testConfigWithBooleanValue(): void
    {
        $this->runMaml('{
            config: {
                ssh_multiplexing: false
            }
        }');

        self::assertFalse($this->deployer->config->get('ssh_multiplexing'));
    }

    public function testHost(): void
    {
        $this->runMaml('{
            hosts: {
                "example.com": {
                    deploy_path: "~/app"
                    remote_user: "deployer"
                }
            }
        }');

        self::assertTrue($this->deployer->hosts->has('example.com'));
        $host = $this->deployer->hosts->get('example.com');
        self::assertEquals('~/app', $host->get('deploy_path'));
        self::assertEquals('deployer', $host->getRemoteUser());
    }

    public function testLocalhost(): void
    {
        $this->runMaml('{
            hosts: {
                "local": {
                    local: true
                    deploy_path: "/tmp/deploy"
                }
            }
        }');

        self::assertTrue($this->deployer->hosts->has('local'));
        self::assertEquals('/tmp/deploy', $this->deployer->hosts->get('local')->get('deploy_path'));
    }

    public function testMultipleHosts(): void
    {
        $this->runMaml('{
            hosts: {
                "prod.example.com": {
                    deploy_path: "/var/www/prod"
                }
                "staging.example.com": {
                    deploy_path: "/var/www/staging"
                }
            }
        }');

        self::assertTrue($this->deployer->hosts->has('prod.example.com'));
        self::assertTrue($this->deployer->hosts->has('staging.example.com'));
        self::assertEquals('/var/www/prod', $this->deployer->hosts->get('prod.example.com')->get('deploy_path'));
        self::assertEquals('/var/www/staging', $this->deployer->hosts->get('staging.example.com')->get('deploy_path'));
    }

    public function testGroupTask(): void
    {
        $this->runMaml('{
            tasks: {
                task_a: [
                    { run: "echo a" }
                ]
                task_b: [
                    { run: "echo b" }
                ]
                deploy: [
                    "task_a"
                    "task_b"
                ]
            }
        }');

        $task = $this->deployer->tasks->get('deploy');
        self::assertInstanceOf(GroupTask::class, $task);
        self::assertEquals(['task_a', 'task_b'], $task->getGroup());
    }

    public function testStepTask(): void
    {
        $this->runMaml('{
            tasks: {
                greet: [
                    { run: "echo hello" }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('greet'));
        $task = $this->deployer->tasks->get('greet');
        self::assertInstanceOf(Task::class, $task);
        self::assertNotInstanceOf(GroupTask::class, $task);
    }

    public function testTaskDesc(): void
    {
        $this->runMaml('{
            tasks: {
                greet: [
                    { desc: "Say hello" }
                    { run: "echo hello" }
                ]
            }
        }');

        $task = $this->deployer->tasks->get('greet');
        self::assertEquals('Say hello', $task->getDescription());
    }

    public function testTaskOnce(): void
    {
        $this->runMaml('{
            tasks: {
                setup: [
                    { once: true }
                    { run: "echo setup" }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->get('setup')->isOnce());
    }

    public function testTaskHidden(): void
    {
        $this->runMaml('{
            tasks: {
                internal: [
                    { hidden: true }
                    { run: "echo internal" }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->get('internal')->isHidden());
    }

    public function testTaskLimit(): void
    {
        $this->runMaml('{
            tasks: {
                migrate: [
                    { limit: 1 }
                    { run: "echo migrate" }
                ]
            }
        }');

        self::assertEquals(1, $this->deployer->tasks->get('migrate')->getLimit());
    }

    public function testTaskSelect(): void
    {
        $this->runMaml('{
            tasks: {
                deploy_prod: [
                    { select: "stage=production" }
                    { run: "echo deploy" }
                ]
            }
        }');

        self::assertNotNull($this->deployer->tasks->get('deploy_prod')->getSelector());
    }

    public function testTaskConfigDoesNotBreakCallbackChain(): void
    {
        $this->runMaml('{
            tasks: {
                my_task: [
                    { desc: "A task with desc before run" }
                    { run: "echo after_desc" }
                ]
            }
        }');

        $task = $this->deployer->tasks->get('my_task');
        self::assertEquals('A task with desc before run', $task->getDescription());

        $reflection = new \ReflectionClass($task);
        $callbackProp = $reflection->getProperty('callback');

        $callback = $callbackProp->getValue($task);
        self::assertIsCallable($callback);
        self::assertInstanceOf(\Closure::class, $callback);
    }

    public function testTaskDescriptionFromComments(): void
    {
        $this->runMaml('{
            tasks: {
                # Deploy the app
                deploy_app: [
                    { run: "echo deploy" }
                ]
            }
        }');

        $task = $this->deployer->tasks->get('deploy_app');
        self::assertEquals('Deploy the app', $task->getDescription());
    }

    public function testMultilineCommentDescription(): void
    {
        $this->runMaml('{
            tasks: {
                # Line one
                # Line two
                multi_desc: [
                    { run: "echo hi" }
                ]
            }
        }');

        $task = $this->deployer->tasks->get('multi_desc');
        self::assertStringContainsString("Line one", $task->getDescription());
        self::assertStringContainsString("Line two", $task->getDescription());
        # After Bug 3 fix, lines joined with real newlines, not literal '\n'.
        self::assertStringNotContainsString('\n', $task->getDescription());
    }

    public function testBeforeHook(): void
    {
        $this->runMaml('{
            tasks: {
                deploy: [
                    { run: "echo deploy" }
                ]
                build: [
                    { run: "echo build" }
                ]
            }
            before: {
                deploy: "build"
            }
        }');

        self::assertContains('build', $this->deployer->tasks->get('deploy')->getBefore());
    }

    public function testAfterHook(): void
    {
        $this->runMaml('{
            tasks: {
                deploy: [
                    { run: "echo deploy" }
                ]
                cleanup: [
                    { run: "echo cleanup" }
                ]
            }
            after: {
                deploy: "cleanup"
            }
        }');

        self::assertContains('cleanup', $this->deployer->tasks->get('deploy')->getAfter());
    }

    public function testBeforeHookWithArray(): void
    {
        $this->runMaml('{
            tasks: {
                deploy: [
                    { run: "echo deploy" }
                ]
                check: [
                    { run: "echo check" }
                ]
                build: [
                    { run: "echo build" }
                ]
            }
            before: {
                deploy: ["check", "build"]
            }
        }');

        $before = $this->deployer->tasks->get('deploy')->getBefore();
        self::assertContains('check', $before);
        self::assertContains('build', $before);
    }

    public function testAfterHookWithArray(): void
    {
        $this->runMaml('{
            tasks: {
                deploy: [
                    { run: "echo deploy" }
                ]
                notify: [
                    { run: "echo notify" }
                ]
                cleanup: [
                    { run: "echo cleanup" }
                ]
            }
            after: {
                deploy: ["notify", "cleanup"]
            }
        }');

        $after = $this->deployer->tasks->get('deploy')->getAfter();
        self::assertContains('notify', $after);
        self::assertContains('cleanup', $after);
    }

    public function testSchemaValidationError(): void
    {
        $this->expectException(SchemaException::class);

        $this->runMaml('{
            config: "not an object"
        }');
    }

    public function testUnknownTopLevelProperty(): void
    {
        $this->expectException(SchemaException::class);

        $this->runMaml('{
            unknown_key: "value"
        }');
    }

    public function testEmptyRecipe(): void
    {
        $this->runMaml('{}');
        self::assertTrue(true);
    }

    public function testImportSingleString(): void
    {
        $phpFile = tempnam(sys_get_temp_dir(), 'maml_import_') . '.php';
        file_put_contents($phpFile, '<?php \Deployer\set("imported_value", "from_php");');

        try {
            $this->runMaml('{
                import: "' . addslashes($phpFile) . '"
            }');

            self::assertEquals('from_php', $this->deployer->config->get('imported_value'));
        } finally {
            unlink($phpFile);
        }
    }

    public function testImportArray(): void
    {
        $phpFile1 = tempnam(sys_get_temp_dir(), 'maml_import1_') . '.php';
        $phpFile2 = tempnam(sys_get_temp_dir(), 'maml_import2_') . '.php';
        file_put_contents($phpFile1, '<?php \Deployer\set("import_1", "first");');
        file_put_contents($phpFile2, '<?php \Deployer\set("import_2", "second");');

        try {
            $this->runMaml('{
                import: [
                    "' . addslashes($phpFile1) . '"
                    "' . addslashes($phpFile2) . '"
                ]
            }');

            self::assertEquals('first', $this->deployer->config->get('import_1'));
            self::assertEquals('second', $this->deployer->config->get('import_2'));
        } finally {
            unlink($phpFile1);
            unlink($phpFile2);
        }
    }

    public function testTaskWithRunOptions(): void
    {
        $this->runMaml('{
            tasks: {
                my_run_task: [
                    {
                        run: "echo hello"
                        timeout: 300
                        idleTimeout: 60
                        nothrow: true
                        forceOutput: true
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('my_run_task'));
    }

    public function testTaskWithRunLocally(): void
    {
        $this->runMaml('{
            tasks: {
                my_local_task: [
                    {
                        run_locally: "echo local"
                        timeout: 120
                        nothrow: true
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('my_local_task'));
    }

    public function testTaskWithRunLocallyOptions(): void
    {
        $this->runMaml('{
            tasks: {
                local_cwd: [
                    {
                        run_locally: "ls"
                        cwd: "/tmp"
                        shell: "/bin/bash"
                        forceOutput: true
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('local_cwd'));
    }

    public function testTaskWithCdStep(): void
    {
        $this->runMaml('{
            tasks: {
                my_cd_task: [
                    { cd: "/var/www" }
                    { run: "pwd" }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('my_cd_task'));
    }

    public function testTaskWithUploadStep(): void
    {
        $this->runMaml('{
            tasks: {
                my_upload_task: [
                    {
                        upload: {
                            src: "local/file.txt"
                            dest: "/remote/path"
                        }
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('my_upload_task'));
        $task = $this->deployer->tasks->get('my_upload_task');
        $reflection = new \ReflectionClass($task);
        $callbackProp = $reflection->getProperty('callback');

        $callback = $callbackProp->getValue($task);
        self::assertIsCallable($callback);
        self::assertInstanceOf(\Closure::class, $callback);
    }

    public function testTaskWithUploadArraySrc(): void
    {
        $this->runMaml('{
            tasks: {
                my_upload_multi: [
                    {
                        upload: {
                            src: ["file1.txt", "file2.txt"]
                            dest: "/remote/path"
                        }
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('my_upload_multi'));
    }

    public function testTaskWithDownloadStep(): void
    {
        $this->runMaml('{
            tasks: {
                my_download_task: [
                    {
                        download: {
                            src: "/remote/file.txt"
                            dest: "local/path"
                        }
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('my_download_task'));
        $task = $this->deployer->tasks->get('my_download_task');
        $reflection = new \ReflectionClass($task);
        $callbackProp = $reflection->getProperty('callback');

        $callback = $callbackProp->getValue($task);
        self::assertIsCallable($callback);
        self::assertInstanceOf(\Closure::class, $callback);
    }

    public function testTaskWithEnvAndSecrets(): void
    {
        $this->runMaml('{
            tasks: {
                env_task: [
                    {
                        run: "echo $GREETING"
                        env: {
                            GREETING: "Hello"
                        }
                        secrets: {
                            API_KEY: "secret123"
                        }
                    }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('env_task'));
    }

    public function testMultipleStepsChained(): void
    {
        $this->runMaml('{
            tasks: {
                multi_step: [
                    { cd: "/var/www" }
                    { run: "echo step1" }
                    { run: "echo step2" }
                ]
            }
        }');

        self::assertTrue($this->deployer->tasks->has('multi_step'));
        $task = $this->deployer->tasks->get('multi_step');
        $reflection = new \ReflectionClass($task);
        $callbackProp = $reflection->getProperty('callback');

        self::assertIsCallable($callbackProp->getValue($task));
    }

    public function testFullRecipe(): void
    {
        $this->runMaml('{
            config: {
                repository: "git@github.com:test/app.git"
                keep_releases: 3
            }

            hosts: {
                "app.example.com": {
                    deploy_path: "/var/www/app"
                    remote_user: "deploy"
                }
                "local_dev": {
                    local: true
                    deploy_path: "/tmp/dev"
                }
            }

            tasks: {
                check: [
                    { run: "echo checking" }
                ]

                # Deploy the application
                full_deploy: [
                    { desc: "Full deployment" }
                    { run: "echo deploying" }
                ]

                all: [
                    "check"
                    "full_deploy"
                ]
            }

            before: {
                full_deploy: "check"
            }

            after: {
                full_deploy: "check"
            }
        }');

        # Config
        self::assertEquals('git@github.com:test/app.git', $this->deployer->config->get('repository'));
        self::assertEquals(3, $this->deployer->config->get('keep_releases'));

        # Hosts
        self::assertTrue($this->deployer->hosts->has('app.example.com'));
        self::assertTrue($this->deployer->hosts->has('local_dev'));

        # Tasks
        self::assertTrue($this->deployer->tasks->has('check'));
        self::assertTrue($this->deployer->tasks->has('full_deploy'));
        self::assertTrue($this->deployer->tasks->has('all'));

        self::assertInstanceOf(GroupTask::class, $this->deployer->tasks->get('all'));

        $deployTask = $this->deployer->tasks->get('full_deploy');
        self::assertEquals('Full deployment', $deployTask->getDescription());
        self::assertContains('check', $deployTask->getBefore());
        self::assertContains('check', $deployTask->getAfter());
    }

    public function testInvalidTaskFormat(): void
    {
        $this->expectException(SchemaException::class);

        $this->runMaml('{
            tasks: "not an object"
        }');
    }

    public function testTaskWithMultipleConfigProperties(): void
    {
        $this->runMaml('{
            tasks: {
                complex_task: [
                    { desc: "Complex" }
                    { once: true }
                    { hidden: true }
                    { limit: 2 }
                    { run: "echo complex" }
                ]
            }
        }');

        $task = $this->deployer->tasks->get('complex_task');
        self::assertEquals('Complex', $task->getDescription());
        self::assertTrue($task->isOnce());
        self::assertTrue($task->isHidden());
        self::assertEquals(2, $task->getLimit());

        # Callback should still be a valid closure after multiple config steps.
        $reflection = new \ReflectionClass($task);
        $callbackProp = $reflection->getProperty('callback');

        self::assertInstanceOf(\Closure::class, $callbackProp->getValue($task));
    }

    public function testFailHook(): void
    {
        $this->runMaml('{
            tasks: {
                deploy: [
                    { run: "echo deploy" }
                ]
                rollback: [
                    { run: "echo rollback" }
                ]
            }
            fail: {
                deploy: "rollback"
            }
        }');

        self::assertEquals('rollback', $this->deployer->fail->get('deploy'));
    }

    public function testFailHookMultipleTasks(): void
    {
        $this->runMaml('{
            tasks: {
                deploy: [
                    { run: "echo deploy" }
                ]
                migrate: [
                    { run: "echo migrate" }
                ]
                rollback: [
                    { run: "echo rollback" }
                ]
                unlock: [
                    { run: "echo unlock" }
                ]
            }
            fail: {
                deploy: "rollback"
                migrate: "unlock"
            }
        }');

        self::assertEquals('rollback', $this->deployer->fail->get('deploy'));
        self::assertEquals('unlock', $this->deployer->fail->get('migrate'));
    }
}
