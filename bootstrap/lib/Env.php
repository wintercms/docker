<?php

namespace Bootstrap;

class Env
{
    protected $allowedEnv = [
        'INSTALL_WINTER' => false,
        'WINTER_BRANCH_OR_TAG' => 'develop',
        'STORM_BRANCH_OR_TAG' => 'develop',
        'WINTER_PR_URL' => null,
        'STORM_PR_URL' => null,
    ];

    protected $envValues = [];

    protected $directory = '/home/ben/winter/test';

    public function __construct()
    {
        foreach ($this->allowedEnv as $name => $default) {
            $this->envValues[$name] = $this->getEnvVar($name) ?? $default;
        }
    }

    /**
     * @return string|bool|null
     */
    public function get(string $name)
    {
        return $this->envValues[$name] ?? null;
    }

    public function root(string $path = ''): string
    {
        return rtrim($this->directory, '/') . '/' . ltrim($path, '/');
    }

    public function out(string $process = 'env', string $message = '')
    {
        echo '[' . $process . '] ' . $message . PHP_EOL;
    }

    /**
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
            if (in_array(strtolower($value), ['1', 'y', 't', 'yes', 'on', 'true'])) {
                return true;
            } elseif (in_array(strtolower($value), ['0', 'n', 'f', 'no', 'off', 'false'])) {
                return false;
            }
        }

        return (string) $value;
    }
}
