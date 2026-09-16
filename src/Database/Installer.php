<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Database;

final class Installer
{
    public const DB_VERSION = '1.0.0';

    private const BRANCHES_TABLE = 'wcbm_branches';
    private const PRODUCT_BRANCH_TABLE = 'wcbm_product_branch';

    /**
     * Plugin activation.
     */
    public function activate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();

        dbDelta(
            $this->getBranchesTableSql(
                $wpdb->prefix,
                $charsetCollate
            )
        );

        dbDelta(
            $this->getProductBranchTableSql(
                $wpdb->prefix,
                $charsetCollate
            )
        );

        $this->insertDefaultBranches();

        update_option(
            'wcbm_db_version',
            self::DB_VERSION
        );
    }

    /**
     * Branches table SQL.
     */
    private function getBranchesTableSql(
        string $prefix,
        string $charsetCollate
    ): string {

        $table = $prefix . self::BRANCHES_TABLE;

        return "CREATE TABLE {$table} (
id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
name VARCHAR(255) NOT NULL,
slug VARCHAR(255) NOT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'active',
created_at DATETIME NOT NULL,
updated_at DATETIME NOT NULL,
PRIMARY KEY (id),
UNIQUE KEY slug (slug)
) {$charsetCollate};";
    }

    /**
     * Product / Branch table SQL.
     */
    private function getProductBranchTableSql(
        string $prefix,
        string $charsetCollate
    ): string {

        $table = $prefix . self::PRODUCT_BRANCH_TABLE;

        return "CREATE TABLE {$table} (
id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
product_id BIGINT(20) UNSIGNED NOT NULL,
branch_id BIGINT(20) UNSIGNED NOT NULL,
regular_price DECIMAL(10,2) DEFAULT NULL,
sale_price DECIMAL(10,2) DEFAULT NULL,
stock_quantity INT(10) UNSIGNED DEFAULT NULL,
manage_stock TINYINT(1) NOT NULL DEFAULT 0,
stock_status VARCHAR(20) NOT NULL DEFAULT 'instock',
is_enabled TINYINT(1) NOT NULL DEFAULT 1,
updated_at DATETIME NOT NULL,
PRIMARY KEY (id),
UNIQUE KEY product_branch (product_id, branch_id),
KEY product_id (product_id),
KEY branch_id (branch_id)
) {$charsetCollate};";
    }

    /**
     * Insert the default branches once.
     */
    private function insertDefaultBranches(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . self::BRANCHES_TABLE;

        $exists = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}"
        );

        if ($exists > 0) {
            return;
        }

        $now = current_time('mysql');

        $branches = [
            [
                'مول عمان',
                'mall-of-oman',
            ],
            [
                'فرع العريمي',
                'al-arimi',
            ],
            [
                'فرع نزوى',
                'nizwa',
            ],
        ];

        foreach ($branches as $branch) {

            $wpdb->insert(
                $table,
                [
                    'name'       => $branch[0],
                    'slug'       => $branch[1],
                    'status'     => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ]
            );
        }
    }
}