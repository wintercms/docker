<?php

namespace Bootstrap;

/**
 * Bootstrap environment.
 *
 * This acts as an encompassing environment for the bootstrap process, handling environment variables, pathing and
 * output.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright 2024 Winter CMS Maintainers
 */
class Env
{
    /**
     * Allowed environment variables. All other environment variables are ignored.
     *
     * @var array<string, bool|string>
     */
    protected $allowedEnv = [
        'INSTALL_WINTER' => false,
        'WINTER_BRANCH_OR_TAG' => 'develop',
        'STORM_BRANCH_OR_TAG' => 'develop',
        'WINTER_PR_URL' => null,
        'STORM_PR_URL' => null,
        'INCLUDE_DEV_DEPENDENCIES' => false,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => 'storage/database.sqlite',
    ];

    /**
     * Processed/available environment variables for this environment.
     *
     * @var array<string, string|bool>
     */
    protected $envValues = [];

    /**
     * Root directory for bootstrapping.
     *
     * @var string
     */
    protected $directory = '/winter';

    /**
     * Constructor.
     */
    public function __construct()
    {
        foreach ($this->allowedEnv as $name => $default) {
            $this->envValues[$name] = $this->getEnvVar($name) ?? $default;
        }
    }

    /**
     * Gets an environment variable value.
     *
     * @return string|bool|null
     */
    public function get(string $name)
    {
        return $this->envValues[$name] ?? null;
    }

    /**
     * Provides a path relative to the root directory.
     */
    public function root(string $path = ''): string
    {
        return rtrim($this->directory, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Outputs a message to the console.
     */
    public function out(string $process = 'env', string $message = ''): void
    {
        echo '[' . $process . '] ' . $message . PHP_EOL;
    }

    /**
     * Gets the environment variable from the system.
     *
     * The environment variable must be defined in the system - this will not accept environment variables defined
     * in script.
     *
     * The value of the environment variable will always be a string, unless it is boolean-like (ie. "true", "yes",
     * "on", etc.), in which case it will be coerced to a boolean. Note that 1 and 0 are not considered boolean-like in
     * this case in order to allow for them to act as numeric values.
     *
     * @return string|bool|null
     */
    protected function getEnvVar($name)
    {
        $value = getenv($name, true);

        if ($value === false) {
            return null;
        }

        // Coerce booleans (for env vars with a default value that is a boolean)
        if (is_bool($this->allowedEnv[$name])) {
            if (in_array(strtolower($value), ['y', 't', 'yes', 'on', 'true'])) {
                return true;
            } elseif (in_array(strtolower($value), ['n', 'f', 'no', 'off', 'false'])) {
                return false;
            }
        }

        return (string) $value;
    }
}
