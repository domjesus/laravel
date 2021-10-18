<?php

namespace Deployer;

// Include the Laravel & rsync recipes
require 'recipe/laravel.php';
require 'recipe/rsync.php';

set('application', 'My App');
set('ssh_multiplexing', true); // Speeds up deployments

set('rsync_src', function () {
    return 'public_html/laravel_ci_cd'; // If your project isn't in the root, you'll need to change this.
});

// Configuring the rsync exclusions.
// You'll want to exclude anything that you don't want on the production server.
add('rsync', [
    'exclude' => [
        '.git',
        '/.env',
        '/storage/',
        '/vendor/',
        '/node_modules/',
        '.github',
        'deploy.php',
    ],
]);


// Set up a deployer task to copy secrets to the server.
// Since our secrets are stored in Gitlab, we can access them as env vars.
task('deploy:secrets', function () {
    file_put_contents(__DIR__ . '/.env', getenv('DOT_ENV'));
    upload('.env', get('deploy_path') . '/shared');
});

// Production Server
host('production') // Name of the server
->hostname('185.201.10.52') // Hostname or IP address
->port('65002')
->stage('production') // Deployment stage (production, staging, etc)
->user('u731098780') // SSH user
->set('deploy_path', '/public_html/laravel_ci_cd'); // Deploy path

// Staging Server
host('teletrabalho.net') // Name of the server
->hostname('185.201.10.52') // Hostname or IP address
->port('65002')
->stage('staging') // Deployment stage (production, staging, etc)
->user('u731098780') // SSH user
->set('deploy_path', '/public_html/laravel_ci_cd_stagging'); // Deploy path

after('deploy:failed', 'deploy:unlock'); // Unlock after failed deploy

desc('Deploy the application');

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync', // Deploy code & built assets
    'deploy:secrets', // Deploy secrets
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'artisan:storage:link', // |
    'artisan:view:cache',   // |
    'artisan:config:cache', // | Laravel Specific steps
    //'artisan:optimize',     // |
    'artisan:migrate',      // |
    'artisan:queue:restart',// |
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]);
