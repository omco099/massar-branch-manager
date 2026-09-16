<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Core;

use wpdb;
use Alnaseeg\BranchManager\Branch\BranchContext;
use Alnaseeg\BranchManager\Branch\BranchRepository;
use Alnaseeg\BranchManager\Branch\BranchResolver;
use Alnaseeg\BranchManager\Branch\BranchSelector;
use Alnaseeg\BranchManager\Branch\BranchSelectorRenderer;
use Alnaseeg\BranchManager\Branch\BranchSession;
use Alnaseeg\BranchManager\Branch\PageBranchResolver;
use Alnaseeg\BranchManager\Cart\BranchCartManager;
use Alnaseeg\BranchManager\Cart\CartValidator;
use Alnaseeg\BranchManager\Catalog\BranchCatalogFilter;
use Alnaseeg\BranchManager\Catalog\BranchCatalogService;
use Alnaseeg\BranchManager\Checkout\OrderMetaManager;
use Alnaseeg\BranchManager\Product\ProductBranchManager;
use Alnaseeg\BranchManager\Product\ProductRepository;
use Alnaseeg\BranchManager\Shortcodes\BranchProductsShortcode;

/**
 * Creates and stores application services.
 */
final class Services
{
    /**
     * Cached service instances.
     *
     * @var array<string,object>
     */
    private array $services = [];

    public function __construct(
        private readonly wpdb $wpdb
    ) {
    }

    /**
     * Branch repository.
     */
    public function branchRepository(): BranchRepository
    {
        return $this->services[__METHOD__]
            ??= new BranchRepository($this->wpdb);
    }

    /**
     * Branch session.
     */
    public function branchSession(): BranchSession
    {
        return $this->services[__METHOD__]
            ??= new BranchSession();
    }

    /**
     * Branch context.
     */
    public function branchContext(): BranchContext
    {
        return $this->services[__METHOD__]
            ??= new BranchContext();
    }

    /**
     * Resolve branch from current page.
     */
    public function pageBranchResolver(): PageBranchResolver
    {
        return $this->services[__METHOD__]
            ??= new PageBranchResolver(
                $this->branchRepository()
            );
    }

    /**
     * Branch resolver.
     */
    public function branchResolver(): BranchResolver
    {
        return $this->services[__METHOD__]
            ??= new BranchResolver(
                $this->branchRepository(),
                $this->branchSession(),
                $this->branchContext(),
                $this->pageBranchResolver()
            );
    }

    /**
     * Branch selector.
     *
     * Kept for legacy support.
     * It is not registered in Version 1.
     */
    public function branchSelector(): BranchSelector
    {
        return $this->services[__METHOD__]
            ??= new BranchSelector(
                $this->branchRepository(),
                $this->branchSession(),
                $this->branchSelectorRenderer()
            );
    }

    /**
     * Branch selector renderer.
     *
     * Kept for legacy support.
     */
    public function branchSelectorRenderer(): BranchSelectorRenderer
    {
        return $this->services[__METHOD__]
            ??= new BranchSelectorRenderer();
    }

    /**
     * Product repository.
     */
    public function productRepository(): ProductRepository
    {
        return $this->services[__METHOD__]
            ??= new ProductRepository($this->wpdb);
    }

    /**
     * Product branch manager.
     */
    public function productBranchManager(): ProductBranchManager
    {
        return $this->services[__METHOD__]
            ??= new ProductBranchManager(
                $this->branchResolver(),
                $this->branchRepository(),
                $this->productRepository()
            );
    }

    /**
     * Branch catalog service.
     */
    public function branchCatalogService(): BranchCatalogService
    {
        return $this->services[__METHOD__]
            ??= new BranchCatalogService(
                $this->branchResolver(),
                $this->productRepository()
            );
    }

    /**
     * Cart validator.
     */
    public function cartValidator(): CartValidator
    {
        return $this->services[__METHOD__]
            ??= new CartValidator(
                $this->branchResolver(),
                $this->productRepository()
            );
    }

    /**
    * Branch cart manager.
    */
    public function branchCartManager(): BranchCartManager
    {
        return $this->services[__METHOD__]
            ??= new BranchCartManager();
    }

    /**
     * Branch catalog filter.
     */
    public function branchCatalogFilter(): BranchCatalogFilter
    {
        return $this->services[__METHOD__]
            ??= new BranchCatalogFilter(
                $this->branchCatalogService()
            );
    }

    /**
     * Branch products shortcode.
     */
    public function branchProductsShortcode(): BranchProductsShortcode
    {
        return $this->services[__METHOD__]
            ??= new BranchProductsShortcode(
                $this->branchCatalogService()
            );
    }

    /**
     * Order meta manager.
     */
    public function orderMetaManager(): OrderMetaManager
    {
        return $this->services[__METHOD__]
            ??= new OrderMetaManager(
                $this->branchRepository()
        );
    }
}