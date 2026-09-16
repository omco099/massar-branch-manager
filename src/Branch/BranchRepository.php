<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

use Alnaseeg\BranchManager\Contracts\BranchRepositoryInterface;
use wpdb;

final class BranchRepository implements BranchRepositoryInterface
{
    /**
     * Branches table name.
     */
    private string $table;

    /**
     * WordPress database instance.
     */
    public function __construct(
        private readonly wpdb $database
    ) {
        $this->table = $database->prefix . 'wcbm_branches';
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Branch
    {
        $row = $this->database->get_row(
            $this->database->prepare(
                "
                SELECT id, name, slug, status
                FROM {$this->table}
                WHERE id = %d
                LIMIT 1
                ",
                $id
            ),
            ARRAY_A
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug): ?Branch
    {
        $row = $this->database->get_row(
            $this->database->prepare(
                "
                SELECT id, name, slug, status
                FROM {$this->table}
                WHERE slug = %s
                LIMIT 1
                ",
                $slug
            ),
            ARRAY_A
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        $rows = $this->database->get_results(
            "
            SELECT id, name, slug, status
            FROM {$this->table}
            WHERE status = 'active'
            ORDER BY id ASC
            ",
            ARRAY_A
        );

        if (empty($rows)) {
            return [];
        }

        $branches = [];

        foreach ($rows as $row) {
            $branches[] = $this->hydrate($row);
        }

        return $branches;
    }

    /**
     * Hydrate Branch entity.
     *
     * @param array<string,mixed> $row
     */
    private function hydrate(array $row): Branch
    {
        return new Branch(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['slug'],
            (string) $row['status']
        );
    }
}