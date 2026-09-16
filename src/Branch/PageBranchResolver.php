<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Branch;

use WP_Post;

/**
 * Resolves the current branch from the current page slug.
 */
final class PageBranchResolver
{
    /**
     * Create a new resolver instance.
     */
    public function __construct(
        private readonly BranchRepository $branches
    ) {
    }

    /**
     * Resolve the current branch from the current page.
     */
    public function resolve(): ?Branch
    {
        if (!is_page()) {
            return null;
        }

        $page = get_queried_object();

        if (!$page instanceof WP_Post) {
            return null;
        }

        if (empty($page->post_name)) {
            return null;
        }

        $branch = $this->branches->findBySlug(
            $page->post_name
        );

        if ($branch === null) {
            return null;
        }

        if (!$branch->isActive()) {
            return null;
        }

        return $branch;
    }
}