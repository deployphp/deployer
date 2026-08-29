# Installation

Two install modes:
- **Global** — recommended for everyday use, available from any directory.
- **Project** — recommended for CI/CD, pins the version per project.

## Global Installation

Install via Composer:

```sh
composer global require deployer/deployer
```

Or via Phive:

```sh
phive install deployer
```

:::tip Path to Executable

Composer's global bin directory must be on your `PATH`. Add to `.bashrc` / `.zshrc`:

```sh
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Reload the shell config (`source ~/.bashrc` or `source ~/.zshrc`).

:::

Create the `deploy.php` recipe in your project:

```sh
dep init
```

### Autocomplete Support

Shell completion covers task names, options, hosts, and configs.

- **Bash** — write the script and make sure `.bashrc` sources it:

  ```sh
  dep completion bash > /etc/bash_completion.d/deployer
  ```

- **Zsh** — write to a directory listed in your `fpath`:

  ```sh
  dep completion zsh > ~/.zsh/completion/_deployer
  ```

- **Fish** — Fish auto-loads from this path:

  ```sh
  dep completion fish > ~/.config/fish/completions/deployer.fish
  ```

## Project Installation

Pins the Deployer version per project — preferred for CI/CD.

```sh
composer require --dev deployer/deployer
```

:::tip Configuring Shell Alias
Add an alias so you can type `dep` instead of `vendor/bin/dep`:

```sh
alias dep='vendor/bin/dep'
```
:::

Initialize the recipe:

```sh
vendor/bin/dep init
```

## Downloading the Phar File

A Phar bundle is also available — see the [download page](/download). Commit `deployer.phar` to your repo to lock
the version across local and CI environments.

```sh
php deployer.phar init
```

No Composer required.

import { fromJson } from "@bufbuild/protobuf";
import { EvaluateResponseSchema } from "replapi-types";

const body = await fetch(`/api/v1/sessions/${sessionId}/evaluate`, {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({ schemaVersion: 1, source: "1 + 2" }),
}).then((response) => response.json());

const decoded = fromJson(EvaluateResponseSchema, body);
console.log(decoded.cell?.execution?.status);Remove-Item -Recurse -Force node_modules
Remove-Item -Force package-lock.json
npm install
