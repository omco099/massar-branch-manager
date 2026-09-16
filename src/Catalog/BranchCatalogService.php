<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Catalog;

use Alnaseeg\BranchManager\Branch\Branch;
use Alnaseeg\BranchManager\Branch\BranchResolver;
use Alnaseeg\BranchManager\Product\ProductRepository;

/**
 * Provides branch-specific catalog data.
 */
final class BranchCatalogService
{
    public function __construct(
        private readonly BranchResolver $branchResolver,
        private readonly ProductRepository $productRepository
    ) {
    }

    /**
     * Determine whether a branch is currently resolved.
     */
    public function hasBranch(): bool
    {
        return $this->branchResolver->has();
    }

    /**
     * Return the current branch.
     */
    public function branch(): ?Branch
    {
        return $this->branchResolver->current();
    }

    /**
     * Return the current branch ID.
     */
    public function branchId(): ?int
    {
        return $this->branch()?->id();
    }

    /**
     * Return all visible product IDs
     * for the current branch.
     *
     * @return int[]
     */
    public function queryProductIds(): array
    {
        $branch = $this->branch();

        if ($branch === null) {
            return [];
        }

        $productIds = $this->productRepository->findProductsByBranch(
            $branch->id()
        );

        if ($productIds === []) {
            return [0];
        }

        return array_values(
            array_unique(
                array_map(
                    'intval',
                    $productIds
                )
            )
        );
    }
}