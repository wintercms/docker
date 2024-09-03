<?php

namespace Bootstrap;

require_once __DIR__ . '/lib/Env.php';
require_once __DIR__ . '/lib/Installer.php';
require_once __DIR__ . '/lib/Process.php';
require_once __DIR__ . '/lib/Promise.php';

$env = new Env();

// Check if Winter needs to be installed
if ($env->get('INSTALL_WINTER')) {
    Installer::setEnv($env);

    if (!is_null($env->get('WINTER_PR_URL'))) {
        Installer::installWinterFromPR($env->get('WINTER_PR_URL'));
    } else {
        Installer::installWinter($env->get('WINTER_BRANCH_OR_TAG'));
    }
}
