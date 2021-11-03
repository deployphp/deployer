<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutocompleteCommand extends Command
{
    private $deployer;

    public function __construct(Deployer $deployer)
    {
        $this->deployer = $deployer;
        parent::__construct('autocomplete');
    }

    protected function configure()
    {
        $this
            ->setDescription('Add CLI autocomplete')
            ->setDefinition(array(
                new InputOption('shell', null, InputOption::VALUE_REQUIRED, 'Shell type ("bash" or "zsh")', isset($_SERVER['SHELL']) ? basename($_SERVER['SHELL'], '.exe') : null),
                new InputOption('query', null, InputOption::VALUE_NONE),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!empty($input->getOption('query'))) {
            return $this->query($input, $output);
        }

        $shell = $input->getOption('shell');
        if (!in_array($shell, ['bash', 'zsh'])) {
            throw new InvalidArgumentException("Completion is only available for bash and zsh, \"{$shell}\" given.");
        }
        $output->write($this->$shell());
        return 0;
    }

    private function bash(): string
    {
        return <<<'BASH'
_dep()
{
    local cur script com opts hosts
    COMPREPLY=()
    _get_comp_words_by_ref -n : cur words

    # for an alias, get the real script behind it
    if [[ $(type -t ${words[0]}) == "alias" ]]; then
        script=$(alias ${words[0]} | sed -E "s/alias ${words[0]}='(.*)'/\1/")
    else
        script=${words[0]}
    fi

    # lookup for command
    for word in ${words[@]:1}; do
        if [[ $word != -* ]]; then
            com=$word
            break
        fi
    done

    # completing for an option
    if [[ ${cur} == --* ]] ; then
        opts=$script
        [[ -n $com ]] && opts=$opts" -h "$com
        opts=$($opts --no-ansi 2>/dev/null | sed -n '/Options/,/^$/p' | sed -e '1d;$d' | sed 's/[^--]*\(--.*\)/\1/' | sed -En 's/[^ ]*(-(-[[:alnum:]]+){1,}).*/\1/p' | awk '{$1=$1};1'; exit ${PIPESTATUS[0]});
        [[ $? -eq 0 ]] || return 0;
        COMPREPLY=($(compgen -W "${opts}" -- ${cur}))
        __ltrim_colon_completions "$cur"

        return 0
    fi

    # completing for a command
    if [[ $cur == $com ]]; then
        coms=$($script list --raw 2>/dev/null | awk '{print $1}'; exit ${PIPESTATUS[0]})
        [[ $? -eq 0 ]] || return 0;
        COMPREPLY=($(compgen -W "${coms}" -- ${cur}))
        __ltrim_colon_completions "$cur"

        return 0;
    fi
    
    # completing for a host
    hosts=$($script autocomplete --query)
    [[ $? -eq 0 ]] || return 0;
    COMPREPLY=($(compgen -W "${hosts}" -- ${cur}))
    __ltrim_colon_completions "$cur"
    return 0;
}

complete -o default -F _dep dep

BASH;
    }

    private function zsh(): string
    {
        return <<<'ZSH'
_dep()
{
    local state com cur commands options hosts

    cur=${words[${#words[@]}]}

    # lookup for command
    for word in ${words[@]:1}; do
        if [[ $word != -* ]]; then
            com=$word
            break
        fi
    done

    [[ ${cur} == --* ]] && state="option"
    [[ $cur == $com ]] && state="command"
    state="hosts" 

    case $state in
        command)
            commands=("${(@f)$(${words[1]} list --no-ansi --raw 2>/dev/null | awk '{ gsub(/:/, "\\:", $1); print }' | awk '{if (NF>1) print $1 ":" substr($0, index($0,$2)); else print $1}')}")
            _describe 'command' commands
        ;;
        option)
            options=("${(@f)$(${words[1]} -h ${words[2]} --no-ansi 2>/dev/null | sed -n '/Options/,/^$/p' | sed -e '1d;$d' | sed 's/[^--]*\(--.*\)/\1/' | sed -En 's/[^ ]*(-(-[[:alnum:]]+){1,})[[:space:]]+(.*)/\1:\3/p' | awk '{$1=$1};1')}")
            _describe 'option' options
        ;;
        hosts)
            hosts=("${(@f)$(${words[1]} autocomplete --query)}")
            _describe 'hosts' hosts
        *)
            # fallback to file completion
            _arguments '*:file:_files'
    esac
}

compdef _dep dep

ZSH;
    }

    private function query(InputInterface $input, OutputInterface $output): int
    {
        $response = ['all'];
        $configs = [];
        foreach ($this->deployer->hosts as $host) {
            $configs[$host->getAlias()] = $host->config()->persist();
        }
        foreach ($configs as $alias => $c) {
            $response[] = $alias;
            foreach ($c['labels'] ?? [] as $label => $value) {
                $response[] = "$label=$value";
            }
        }
        $output->writeln(array_unique($response));
        return 0;
    }
}
