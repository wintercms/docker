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

// Get nobody user
$env->out('bootstrap', 'Get nobody user');
$user = posix_getpwnam('nobody');
if (!$user) {
    exec('useradd nobody');
    exec('groupadd nobody');
    $user = posix_getpwnam('nobody');
}

// Change permissions
$env->out('bootstrap', 'Change permissions of necessary directories');
exec('chown -R nobody:nobody /winter /bootstrap /run /var/lib/nginx /var/log/nginx');

// Change to nobody user
$env->out('bootstrap', 'Change to nobody user');
posix_setuid($user['uid']);
posix_setgid($user['gid']);

// Remove bootstrap
$env->out('bootstrap', 'Remove bootstrap');
exec('rm -rf /bootstrap/*');

// Run web server
$env->out('bootstrap', 'Run web server');
pclose(popen('/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf >/dev/stdout 2>&1 &', 'r'));

