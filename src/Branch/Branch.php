<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

/**
 * Represents a store branch.
 *
 * This class is a pure domain entity.
 * It contains no WordPress, WooCommerce,
 * database or framework dependencies.
 */
final class Branch
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly string $status
    ) {
    }

    /**
     * Get branch ID.
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Get branch name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get branch slug.
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * Get branch status.
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * Determine whether the branch is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}