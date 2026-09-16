<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

/**
 * Holds the current branch during the request lifecycle.
 */
final class BranchContext
{
    private ?Branch $currentBranch = null;

    /**
     * Set the current branch.
     */
    public function set(Branch $branch): void
    {
        $this->currentBranch = $branch;
    }

    /**
     * Get the current branch.
     */
    public function current(): ?Branch
    {
        return $this->currentBranch;
    }

    /**
     * Determine whether a branch has been resolved.
     */
    public function has(): bool
    {
        return $this->currentBranch !== null;
    }

    /**
     * Clear the current branch.
     */
    public function clear(): void
    {
        $this->currentBranch = null;
    }
}