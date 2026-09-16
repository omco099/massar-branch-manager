<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

final class BranchSession
{
    /**
     * WooCommerce session key.
     */
    private const SESSION_KEY = 'wcbm_branch_id';

    /**
     * Store the current branch ID.
     */
    public function set(int $branchId): void
    {
        if (! function_exists('WC')) {
            return;
        }

        $session = WC()->session;

        if ($session === null) {
            return;
        }

        $session->set(self::SESSION_KEY, $branchId);
    }

    /**
     * Get the current branch ID.
     */
    public function get(): ?int
    {
        if (! function_exists('WC')) {
            return null;
        }

        $session = WC()->session;

        if ($session === null) {
            return null;
        }

        $branchId = $session->get(self::SESSION_KEY);

        if ($branchId === null) {
            return null;
        }

        return (int) $branchId;
    }

    /**
     * Determine whether a branch has been selected.
     */
    public function has(): bool
    {
        return $this->get() !== null;
    }

    /**
     * Remove the current branch from the session.
     */
    public function clear(): void
    {
        if (! function_exists('WC')) {
            return;
        }

        $session = WC()->session;

        if ($session === null) {
            return;
        }

        $session->set(self::SESSION_KEY, null);
    }
}