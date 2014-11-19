/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$(function () {
    var code = $('.code');
    var console = $('.console');
    var input = $('input').focus();
    var form = $('form');
    var scroll = function () {
        code.scrollTop(code[0].scrollHeight);
    };

    var notFound;
    var delay = function (answer) {
        console.append(answer.shift());
        scroll();
        if (answer.length > 0) {
            setTimeout(function () {
                delay(answer);
            }, 1000);
        }
    };

    var to = function (command, need, answer) {
        if (need.indexOf(command) != -1) {

            if (Object.prototype.toString.call(answer) === '[object Array]') {
                delay(answer);
            } else {
                console.append(answer);
            }
            notFound = false;
        }
    };

    var delayInput = function (command) {
        input.val(input.val() + command.shift());
        if (command.length > 0) {
            setTimeout(function () {
                delayInput(command);
            }, 200);
        }
    };

    setTimeout(function () {
        delayInput('dep deploy'.split(''));
    }, 1000);

    input.autocomplete({
        '^\\w*$': ['ls', 'pwd', 'dep', 'help', 'php -v', 'boobs', 'tit'],
        '^dep \\w*$': ['deploy', 'rollback', 'migrate', 'help', 'list']
    });

    form.submit(function (event) {
        event.stopPropagation();

        var command = $.trim(input.val());
        if (command == '') {
            return false;
        }

        console.append('&gt; ' + command + '\n');

        notFound = true;

        to(command, ['help', '?', '/?'], 'This is an example of the console to try Deployer in your browser.\n' +
            'Try type the following commands and press enter:\n' +
            'dep\n' +
            'dep deploy\n' +
            'dep rollback\n' +
            'ls\n');

        to(command, ['dep', 'dep help', 'dep list'], 'Deployer\n' +
            '\n' +
            'Usage:\n' +
            '  dep [command] [options]\n' +
            '\n' +
            'Available commands:\n' +
            '  help          Displays help for a command\n' +
            '  list          Lists commands\n' +
            '  deploy        Deploy project\n' +
            '  rollback      Rollback to previous release\n' +
            '  migrate       Migrate database\n');

        to(command, ['ls'], 'bin\n' +
            'src\n' +
            'vendor\n' +
            '.gitignore\n' +
            'deploy.php\n');

        to(command, ['pwd'], '/home/www\n');

        to(command, ['dep deploy'], [
            'Preparing server for deploy.................................✔\n',
            'Updating code...............................................✔\n',
            'Creating cache dir..........................................✔\n',
            'Creating symlinks for shared files..........................✔\n',
            'Normalizing asset timestamps................................✔\n',
            'Installing vendors..........................................✔\n',
            'Dumping assets..............................................✔\n',
            'Warming up cache............................................✔\n',
            'Cleaning up old releases....................................✔\n',
            '<i>Successfully deployed!</i>\n']);

        to(command, ['dep rollback'], [
            'Restoring previous releases.................................✔\n',
            '<i>Successfully restored!</i>\n']);

        to(command, ['dep migrate'], [
            'Migrating prod database.....................................✔\n',
            '<i>Successfully migrated!</i>\n']);

        to(command, ['php', 'php -v'], 'PHP 6.0.0 (cli) (built: Feb 30 2016 10:96:69)\n');

        to(command, ['tit', 'boobs'], ['<img src="assets/img/tit.gif" alt="">\n', '']);

        if (notFound) {
            console.append('Sorry but this command can not be run in the emulator.\n');
        }

        scroll();
        input.val('');

        return false;
    });

    $(document).keydown(function (event) {
        if (!event.ctrlKey && !event.altKey && !event.metaKey) {
            input.focus();
        }
    });
});
