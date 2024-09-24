<?php

namespace Bootstrap;

require_once __DIR__ . '/lib/Env.php';
require_once __DIR__ . '/lib/Installer.php';
require_once __DIR__ . '/lib/Process.php';
require_once __DIR__ . '/lib/Promise.php';

set_time_limit(0);

$env = new Env();

// Check if Winter needs to be installed
if ($env->get('INSTALL_WINTER')) {
    Installer::setEnv($env);

    if (!Installer::isWinterInstalled()) {
        if (!is_null($env->get('WINTER_PR_URL'))) {
            Installer::installWinterFromPR($env->get('WINTER_PR_URL'));
        } else {
            Installer::installWinter($env->get('WINTER_BRANCH_OR_TAG'));
        }

        if ($env->get('STORM_PR_URL')) {
            Installer::switchStormToPR($env->get('STORM_PR_URL'));
        } elseif ($env->get('STORM_BRANCH_OR_TAG') !== $env->get('WINTER_BRANCH_OR_TAG')) {
            Installer::switchStorm($env->get('STORM_BRANCH_OR_TAG'));
        }

        Installer::getComposerDependencies($env->get('INCLUDE_DEV_DEPENDENCIES'));
    }
}

// Create or touch SQLite database if it's being used
if ($env->get('DB_CONNECTION') === 'sqlite') {
    $env->out('bootstrap', 'Touching SQLite database file');
    Process::exec('touch ' . $env->get('DB_DATABASE'))->discard();
}

// Run migrations
$env->out('bootstrap', 'Running migrations');
Process::exec('php artisan migrate')->discard();
