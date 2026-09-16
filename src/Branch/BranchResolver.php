<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

/**
 * Resolves the current branch.
 */
final class BranchResolver
{
    /**
     * Create a new resolver instance.
     */
    public function __construct(
        private readonly BranchRepository $branches,
        private readonly BranchSession $session,
        private readonly BranchContext $context,
        private readonly PageBranchResolver $pageResolver
    ) {
    }

    /**
     * Resolve the current branch.
     */
    public function resolve(): ?Branch
    {
        /*
         * Version 1:
         *
         * A real branch page always has the highest priority.
         *
         * This is important because another WooCommerce hook
         * may have restored an old branch from the session
         * earlier during the same request.
         */
        $pageBranch = $this->pageResolver->resolve();

        if ($pageBranch !== null) {

            /*
             * Always make the current branch page
             * the active request context.
             */
            $this->context->set($pageBranch);

            /*
             * Persist the branch so product pages,
             * Add to Cart, Cart and Checkout retain
             * the branch after leaving the branch page.
             */
            $this->session->set(
                $pageBranch->id()
            );

            return $pageBranch;
        }

        /*
         * If no branch page is currently being viewed,
         * use the branch already resolved during
         * this request.
         */
        if ($this->context->has()) {
            return $this->context->current();
        }

        /*
         * Restore the branch from WooCommerce session.
         */
        $branchId = $this->session->get();

        if ($branchId === null) {
            return null;
        }

        $branch = $this->branches->findById(
            $branchId
        );

        /*
         * The stored branch no longer exists.
         */
        if ($branch === null) {

            $this->session->clear();
            $this->context->clear();

            return null;
        }

        /*
         * Do not restore an inactive branch.
         */
        if (!$branch->isActive()) {

            $this->session->clear();
            $this->context->clear();

            return null;
        }

        /*
         * Restore the branch into the current
         * request context.
         */
        $this->context->set($branch);

        return $branch;
    }

    /**
     * Get the current branch if available.
     */
    public function current(): ?Branch
    {
        return $this->resolve();
    }

    /**
     * Determine whether a branch has been resolved.
     */
    public function has(): bool
    {
        return $this->resolve() !== null;
    }

    /**
     * Clear the resolved branch.
     */
    public function clear(): void
    {
        $this->context->clear();
        $this->session->clear();
    }
}