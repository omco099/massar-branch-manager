<?php

declare(strict_types=1);

/*
Plugin Name: Massar Branch Manager
Description: Branch management for WooCommerce.
Version: 0.1.0
Author: Massar
Text Domain: massar-branch-manager
Domain Path: /languages
*/

use Alnaseeg\BranchManager\Core\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

define(
    'MASSAR_BRANCH_MANAGER_PLUGIN_FILE',
    __FILE__
);

define(
    'MASSAR_BRANCH_MANAGER_PLUGIN_DIR',
    plugin_dir_path(__FILE__)
);

define(
    'MASSAR_BRANCH_MANAGER_PLUGIN_URL',
    plugin_dir_url(__FILE__)
);

define(
    'MASSAR_BRANCH_MANAGER_PLUGIN_VERSION',
    '0.1.0'
);

require_once __DIR__ . '/autoload.php';

require_once __DIR__ . '/bootstrap/bootstrap.php';

/**
 * Load plugin translations.
 */
add_action(
    'plugins_loaded',
    static function (): void {
        load_plugin_textdomain(
            'massar-branch-manager',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
);

/**
 * Boot the plugin.
 */
(new Plugin())->boot();