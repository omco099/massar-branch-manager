<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Contracts;

use Alnaseeg\BranchManager\Branch\Branch;

interface BranchRepositoryInterface
{
    /**
     * Find a branch by its ID.
     */
    public function findById(int $id): ?Branch;

    /**
     * Find a branch by its slug.
     */
    public function findBySlug(string $slug): ?Branch;

    /**
     * Return all active branches.
     *
     * @return Branch[]
     */
    public function all(): array;
}