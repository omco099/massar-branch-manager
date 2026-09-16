<?php

declare(strict_types=1);

use Alnaseeg\BranchManager\Database\Installer;

/**
 * Plugin bootstrap.
 *
 * Registers activation hooks and bootstraps the plugin.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('massar_branch_manager_activate')) {

    /**
     * Runs when the plugin is activated.
     *
     * Creates or updates the required database tables.
     *
     * @return void
     */
    function massar_branch_manager_activate(): void
    {
        (new Installer())->activate();
    }
}

/**
 * Register plugin activation hook.
 */
register_activation_hook(
    MASSAR_BRANCH_MANAGER_PLUGIN_FILE,
    'massar_branch_manager_activate'
);