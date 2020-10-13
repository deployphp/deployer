<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Configuration;

use Deployer\Utility\Httpie;
use function Deployer\get;
use Deployer\Exception\ConfigurationException;
use function Deployer\Support\array_merge_alternate;
use function Deployer\Support\is_closure;
use function Deployer\Support\normalize_line_endings;

class Configuration implements \ArrayAccess
{

    const PREDEFINED_PARAMS = [
        'alias', 'application', 'bin/composer', 'bin/git', 'bin/php', 'bin/symlink', 
        'branch', 'cleanup_use_sudo', 'clear_paths', 'clear_use_sudo', 'composer_action', 
        'composer_options', 'config_file', 'copy_dirs', 'current_path', 'deploy_path',
        'env', 'forward_agent', 'git_cache', 'hostname', 'http_group', 'http_user', 
        'identity_file', 'keep_releases', 'log_file', 'master_url', 'php_version',
        'port', 'release_name', 'release_path', 'releases_list', 'remote_user', 
        'repository', 'roles', 'shared_dirs', 'shared_files', 'shell', 
        'ssh_multiplexing', 'sudo_askpass', 'sudo_password', 'use_atomic_symlink', 
        'use_relative_symlink', 'user', 'working_path', 'writable_chmod_mode', 
        'writable_chmod_recursive', 'writable_dirs', 'writable_mode', 
        'writable_recursive', 'writable_use_sudo'];

    private $parent;
    private $values = [];
    private $validations = [];

    public function __construct(Configuration $parent = null)
    {
        $this->parent = $parent;
    }

    public function update(array $values): void
    {
        $this->values = $values;
    }

    public function bind(Configuration $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @param mixed $value
     * @param callable $callback
     */
    public function set(string $name, $value, callable $callback = null): void
    {
        if (is_callable($callback)) {
            $this->validations[$name] = $callback;
        }
        if (array_key_exists($name, $this->validations) 
            && !call_user_func($this->validations[$name], $value)
        ) {
            throw new ConfigurationException("Config option \"$name\" has an invalid value.");
        }
        $closestDistance = 4;
        foreach (self::PREDEFINED_PARAMS as $predefinedParam) {
            if ($name === $predefinedParam) {
                unset($closestParam);
                break;
            }
            $distance = levenshtein($name, $predefinedParam);
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closestParam = $predefinedParam;
            }
        }
        if (isset($closestParam)) {
            print("<fg=yellow>Warning:</> \"$name\" not found in parameters. Did you mean \"$closestParam\"?\n");
        }
        $this->values[$name] = $value;
    }

    public function has(string $name): bool
    {
        $ok = array_key_exists($name, $this->values);
        if ($ok) {
            return true;
        }
        if ($this->parent) {
            return $this->parent->has($name);
        }
        return false;
    }

    public function add(string $name, array $array): void
    {
        if ($this->has($name)) {
            $config = $this->get($name);
            if (!is_array($config)) {
                throw new ConfigurationException("Config option \"$name\" isn't array.");
            }
            $this->set($name, array_merge_alternate($config, $array));
        } else {
            $this->set($name, $array);
        }
    }

    /**
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        if (array_key_exists($name, $this->values)) {
            if (is_closure($this->values[$name])) {
                return $this->values[$name] = $this->parse(call_user_func($this->values[$name]));
            } else {
                return $this->parse($this->values[$name]);
            }
        }

        if ($this->parent) {
            $rawValue = $this->parent->fetch($name);
            if ($rawValue !== null) {
                if (is_closure($rawValue)) {
                    return $this->values[$name] = $this->parse(call_user_func($rawValue));
                } else {
                    return $this->values[$name]= $this->parse($rawValue);
                }
            }
        }

        if ($default !== null) {
            return $this->parse($default);
        }

        throw new ConfigurationException("Config option \"$name\" does not exist.");
    }

    /**
     * @return mixed|null
     */
    protected function fetch(string $name)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        if ($this->parent) {
            return $this->parent->fetch($name);
        }
        return null;
    }

    /**
     * @param string|mixed $value
     * @return string|mixed
     */
    public function parse($value)
    {
        if (is_string($value)) {
            $normalizedValue = normalize_line_endings($value);
            return preg_replace_callback('/\{\{\s*([\w\.\/-]+)\s*\}\}/', [$this, 'parseCallback'], $normalizedValue);
        }

        return $value;
    }

    /**
     * @return array
     */
    public function ownValues()
    {
        return $this->values;
    }

    /**
     * @param array $matches
     * @return mixed|null
     */
    private function parseCallback(array $matches)
    {
        return isset($matches[1]) ? $this->get($matches[1]) : null;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     * @return mixed|null
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
    }

    public function load(): void
    {
        if (!$this->has('master_url')) {
            return;
        }

        $values = Httpie::get($this->get('master_url') . '/load')
            ->body([
                'host' => $this->get('alias'),
            ])
            ->getJson();
        $this->update($values);
    }

    public function save(): void
    {
        if (!$this->has('master_url')) {
            return;
        }

        Httpie::get($this->get('master_url') . '/save')
            ->body([
                'host' => $this->get('alias'),
                'config' => $this->persist(),
            ])
            ->getJson();
    }

    public function persist(): array
    {
        $values = [];
        if ($this->parent !== null) {
            $values = $this->parent->persist();
        }
        foreach ($this->values as $key => $value) {
            if (is_closure($value)) {
                continue;
            }
            $values[$key] = $value;
        }
        return $values;
    }
}
