# Installation

There are two ways to install Deployer: globally or locally. Global installation is recommended for most users, as it
allows you to use Deployer from any directory.
Local (or project) installation is preferred for CI/CD pipelines, as it allows you to use the same version of Deployer
across all environments.

## Global Installation

To install Deployer globally, use one of the following commands in your project directory:

```sh
composer global require deployer/deployer
```

Or:

```sh
phive install deployer
```

:::tip Path to Executable

Make sure that Composer's global bin directory is in your `PATH`. Typically, you can add the following line to your
shell configuration file (e.g., `.bashrc`, `.zshrc`):

```sh
export PATH="$HOME/.composer/vendor/bin:$PATH"

```

After adding this line, reload your shell configuration:

```sh
source ~/.bashrc
```

or, for Zsh:

```sh
source ~/.zshrc
```

:::

To set up Deployer in your project and create the `deploy.php` configuration file, run:

```sh
dep init
```

### Autocomplete Support

Deployer includes support for autocompletion, helping you quickly find task names, options, and hosts. To enable
autocomplete for various shells, use the following commands:


- **Bash**:

  ```sh
  dep completion bash > /etc/bash_completion.d/deployer
  ```

  Make sure your `.bashrc` file sources the generated file so that bash completion works.

- **Zsh**:

  ```sh
  dep completion zsh > ~/.zsh/completion/_deployer
  ```

  Ensure that your `.zshrc` file includes the directory where `_deployer` is located in the `fpath`.

- **Fish**:

  ```sh
  dep completion fish > ~/.config/fish/completions/deployer.fish
  ```

  The generated file will be automatically loaded by Fish.

## Project Installation

The project installation method is recommended for CI/CD pipelines, as it allows you to use the same version of Deployer
across all environments.

To install Deployer in your project, run the following command:

```sh
composer require --dev deployer/deployer
```

:::tip Configuring Shell Alias
To make using Deployer more convenient, you can set up a shell alias. This will allow you to run Deployer commands more
easily. Add the following line to your shell configuration file (e.g., `.bashrc`, `.zshrc`):

```sh
alias dep='vendor/bin/dep'
```

This alias lets you use `dep` instead of typing the full path each time.
:::

Then, to initialize Deployer in your project, use:

```sh
vendor/bin/dep init
```

## Downloading the Phar File

Another option for installing Deployer is to download the Phar file. You can find the latest version on
the [download page](/download).

Adding `deployer.phar` to your project repository is recommended to ensure everyone, including your CI pipeline, uses
the same version of Deployer. This helps maintain consistency across all environments.

Once downloaded, run it in your project directory:

```sh
php deployer.phar init
```

This method provides a simple way to use Deployer without needing Composer.

