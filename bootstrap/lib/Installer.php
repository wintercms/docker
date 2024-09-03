<?php

namespace Bootstrap;

class Installer
{
    protected static Env $env;

    public static function setEnv(Env $env)
    {
        self::$env = $env;
    }

    public static function setupDirectory()
    {
        $directory = self::$env->root();

        if (!is_dir($directory)) {
            self::$env->out('installer', 'Creating directory');

            if (!mkdir($directory, 0755, true)) {
                throw new \Exception('Could not create directory');
            }
        }

        chdir($directory);

        if (!is_writable($directory)) {
            throw new \Exception('Directory is not writable');
        }

        self::$env->out('installer', 'Setting up Git repository');

        $success = Process::exec('git init')->then(function () {
            $exists = Process::exec('git remote show -n origin')->then(function (Process $process) {
                return $process->ok()
                    && strpos($process->stdout(), 'Fetch URL: https://github.com/wintercms/winter.git') !== false;
            }, function () {
                throw new \Exception('Could not check if remote exists');
            });

            if (!$exists) {
                return Process::exec('git remote add origin https://github.com/wintercms/winter.git')->then(function () {
                    return true;
                }, function () {
                    return false;
                });
            }

            return true;
        }, function () {
            return false;
        });

        if ($success) {
            return;
        }

        throw new \Exception('Could not setup directory');
    }

    public static function installWinterFromPR(string $url)
    {
        static::setupDirectory();

        // Ensure URL is pointing to a valid Winter PR
        if (!preg_match('/^https:\/\/github\.com\/wintercms\/winter\/pull\/(\d+)/', $url, $matches)) {
            throw new \Exception('Invalid Winter PR URL');
        }

        $prNumber = (int) $matches[1];

        // Switch to PR branch
        self::$env->out('installer', 'Switching to Winter PR #' . $prNumber);

        $success = Process::exec('git fetch origin pull/' . $prNumber . '/head:pr-' . $prNumber)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            throw new \Exception('Could not fetch PR branch');
        }

        $success = Process::exec('git checkout pr-' . $prNumber)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            throw new \Exception('Could not switch to PR branch');
        }
    }
}
