<?php

namespace Bootstrap;

class Installer
{
    protected static Env $env;

    public static function setEnv(Env $env)
    {
        static::$env = $env;
    }

    public static function isWinterInstalled()
    {
        return (
            is_file(static::$env->root('composer.json'))
            && is_file(static::$env->root('composer.lock'))
            && is_file(static::$env->root('artisan'))
            && is_file(static::$env->root('index.php'))
            && is_dir(static::$env->root('config'))
            && is_dir(static::$env->root('modules/system'))
            && is_dir(static::$env->root('plugins'))
            && is_dir(static::$env->root('storage'))
            && is_dir(static::$env->root('themes'))
            && is_dir(static::$env->root('vendor/laravel/framework'))
            && is_dir(static::$env->root('vendor/winter/storm'))
        );
    }

    public static function installWinter(string $branchOrTag)
    {
        static::setupDirectory();
        chdir(static::$env->root());

        static::$env->out('installer', 'Switching to Winter ' . $branchOrTag);

        $success = Process::exec('git fetch origin ' . $branchOrTag)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeGit();
            throw new \Exception('Could not fetch branch/tag');
        }

        $success = Process::exec('git checkout ' . $branchOrTag)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeGit();
            throw new \Exception('Could not switch to branch/tag');
        }
    }

    public static function installWinterFromPR(string $url)
    {
        // Ensure URL is pointing to a valid Winter PR
        if (!preg_match('/^https:\/\/github\.com\/wintercms\/winter\/pull\/(\d+)/', $url, $matches)) {
            throw new \Exception('Invalid Winter PR URL');
        }

        static::setupDirectory();
        chdir(static::$env->root());

        $prNumber = (int) $matches[1];

        // Switch to PR branch
        static::$env->out('installer', 'Switching to Winter PR #' . $prNumber);

        $success = Process::exec('git fetch origin pull/' . $prNumber . '/head:pr-' . $prNumber)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeGit();
            throw new \Exception('Could not fetch PR branch');
        }

        $success = Process::exec('git checkout pr-' . $prNumber)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeGit();
            throw new \Exception('Could not switch to PR branch');
        }
    }

    public static function switchStorm(string $branchOrTag)
    {
        static::setupStormDirectory();
        chdir(static::$env->root('.storm'));

        static::$env->out('installer', 'Switching to Winter Storm ' . $branchOrTag);

        $success = Process::exec('git fetch origin ' . $branchOrTag)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeStormGit();
            throw new \Exception('Could not fetch Storm branch/tag');
        }

        $success = Process::exec('git checkout ' . $branchOrTag)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeStormGit();
            throw new \Exception('Could not switch to Storm branch/tag');
        }
    }

    public static function switchStormToPR(string $url)
    {
        // Ensure URL is pointing to a valid Winter PR
        if (!preg_match('/^https:\/\/github\.com\/wintercms\/storm\/pull\/(\d+)/', $url, $matches)) {
            throw new \Exception('Invalid Winter Storm PR URL');
        }

        static::setupStormDirectory();
        chdir(static::$env->root('.storm'));

        $prNumber = (int) $matches[1];

        // Switch to PR branch
        static::$env->out('installer', 'Switching to Winter Storm PR #' . $prNumber);

        $success = Process::exec('git fetch origin pull/' . $prNumber . '/head:pr-' . $prNumber)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeStormGit();
            throw new \Exception('Could not fetch Storm PR branch');
        }

        $success = Process::exec('git checkout pr-' . $prNumber)->then(function () {
            return true;
        }, function () {
            return false;
        });

        if (!$success) {
            static::removeStormGit();
            throw new \Exception('Could not switch Storm to PR branch');
        }
    }

    public static function getComposerDependencies(bool $includeDevDependencies = false)
    {
        chdir(static::$env->root());

        static::$env->out('installer', 'Installing Composer dependencies');

        $command = 'composer install --no-interaction --no-progress --no-suggest --no-scripts';

        if ($includeDevDependencies) {
            $command .= ' --dev';
        }

        $return = Process::exec($command)->then(function () {
            return true;
        }, function (Process $process) {
            return $process->stderr();
        });

        static::removeStormGit();
        static::removeGit();

        if ($return === true) {
            return;
        }

        throw new \Exception('Could not install Composer dependencies - ' . $return);
    }

    protected static function setupDirectory()
    {
        $directory = static::$env->root();

        if (!is_dir($directory)) {
            static::$env->out('installer', 'Creating directory');

            if (!mkdir($directory, 0755, true)) {
                throw new \Exception('Could not create directory');
            }
        }

        chdir($directory);

        if (!is_writable($directory)) {
            throw new \Exception('Directory is not writable');
        }

        static::$env->out('installer', 'Setting up Git repository');

        $success = Process::exec('git init')->then(function () {
            $exists = Process::exec('git remote show -n origin')->then(function (Process $process) {
                return $process->ok()
                    && strpos($process->stdout(), 'Fetch URL: https://github.com/wintercms/winter.git') !== false;
            }, function () {
                static::removeGit();
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

    protected static function setupStormDirectory()
    {
        $directory = static::$env->root('.storm');

        if (!is_dir($directory)) {
            static::$env->out('installer', 'Creating Storm directory');

            if (!mkdir($directory, 0755, true)) {
                throw new \Exception('Could not create Storm directory');
            }
        }

        chdir($directory);

        if (!is_writable($directory)) {
            throw new \Exception('Storm Directory is not writable');
        }

        static::$env->out('installer', 'Setting up Git repository for Storm');

        $success = Process::exec('git init')->then(function () {
            $exists = Process::exec('git remote show -n origin')->then(function (Process $process) {
                return $process->ok()
                    && strpos($process->stdout(), 'Fetch URL: https://github.com/wintercms/storm.git') !== false;
            }, function () {
                static::removeGit();
                throw new \Exception('Could not check if remote exists');
            });

            if (!$exists) {
                return Process::exec('git remote add origin https://github.com/wintercms/storm.git')->then(function () {
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
            static::$env->out('installer', 'Modify Composer for custom Storm version');

            $composer = json_decode(file_get_contents(static::$env->root('composer.json')), true);
            $composer['repositories'] = [
                [
                    'type' => 'path',
                    'url' => '.storm'
                ]
            ];
            $composer['require']['winter/storm'] = '*';
            file_put_contents(static::$env->root('composer.json'), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return true;
        }

        throw new \Exception('Could not setup Storm directory');
    }

    protected static function removeGit()
    {
        static::$env->out('installer', 'Removing Git repository');

        chdir(static::$env->root());

        $success = Process::exec('rm -rf .git')->then(function () {
            return true;
        }, function () {
            return false;
        });

        if ($success) {
            return;
        }

        throw new \Exception('Could not remove Git repository');
    }

    protected static function removeStormGit()
    {
        if (!is_dir(static::$env->root('.storm'))) {
            return;
        }

        static::$env->out('installer', 'Removing Storm Git repository');

        chdir(static::$env->root('.storm'));

        $success = Process::exec('rm -rf .git')->then(function () {
            return true;
        }, function () {
            return false;
        });

        if ($success) {
            return;
        }

        throw new \Exception('Could not remove Storm Git repository');
    }
}
