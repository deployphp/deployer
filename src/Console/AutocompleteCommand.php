<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class AutocompleteCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('autocomplete')
            ->setDescription('Install command line autocompletion capabilities')
            ->addOption('--install', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('install')) {
            $output->write(<<<'BASH'
#!/bin/bash

_deployer()
{
    local cur script com opts
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
}

complete -o default -F _deployer dep

BASH
            );
        } else {
            $output->write(<<<'HELP'
To install Deployer autocomplete run one of the following commands:            
            
<comment># Bash (Ubuntu/Debian)</comment>

  dep autocomplete --install | sudo tee /etc/bash_completion.d/deployer

<comment># Bash (Mac OSX with Homebrew "bash-completion")</comment>

  dep autocomplete --install > $(brew --prefix)/etc/bash_completion.d/deployer

<comment># Zsh</comment>

  dep autocomplete --install > ~/.deployer_completion && echo "source ~/.deployer_completion" >> ~/.zshrc

<comment># Fish</comment>

  dep autocomplete --install > ~/.config/fish/completions/deployer.fish

Autocomplete will be working after restarting terminal or you can run "source ~/.bash_profile", etc.

HELP
            );
        }
        return 0;
    }
}
