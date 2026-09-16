<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Support;

use wpdb;

/**
 * WordPress database wrapper.
 */
final class Database
{
    public function __construct(
        private readonly wpdb $wpdb
    ) {
    }

    /**
     * Returns the WordPress database instance.
     */
    public function connection(): wpdb
    {
        return $this->wpdb;
    }

    /**
     * Returns the full table name.
     */
    public function table(string $name): string
    {
        return $this->wpdb->prefix . $name;
    }
}