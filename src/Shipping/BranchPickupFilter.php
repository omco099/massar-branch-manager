<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Shipping;

use Alnaseeg\BranchManager\Branch\Branch;
use Alnaseeg\BranchManager\Branch\BranchRepository;
use WC_Shipping_Rate;

/**
 * Filters WooCommerce Local Pickup rates according to
 * the Massar branch stored on the current cart/package.
 */
final class BranchPickupFilter
{
    /**
     * WooCommerce Local Pickup method ID.
     */
    private const PICKUP_METHOD_ID = 'pickup_location';

    /**
     * Map Massar branch slugs to the exact WooCommerce
     * Local Pickup location names.
     *
     * The WooCommerce pickup rate index is intentionally
     * NOT used for branch matching.
     *
     * @var array<string, string>
     */
    private const BRANCH_PICKUP_LOCATIONS = [
        'mall-of-oman' => 'مول عمان',
        'nizwa'       => 'نزوى',
        'al-arimi'    => 'العريمي',
    ];

    public function __construct(
        private readonly BranchRepository $branchRepository
    ) {
    }

    /**
     * Register WooCommerce hooks.
     */
    public function register(): void
    {
        add_filter(
            'woocommerce_package_rates',
            [$this, 'filterRates'],
            20,
            2
        );
    }

    /**
     * Filter WooCommerce Local Pickup rates.
     *
     * Normal shipping methods are always preserved.
     *
     * The current Massar branch is read from the package
     * cart items using the existing wcbm_branch_id value.
     *
     * @param array<string, WC_Shipping_Rate> $rates
     * @param array<string, mixed>            $package
     *
     * @return array<string, WC_Shipping_Rate>
     */
    public function filterRates(
        array $rates,
        array $package
    ): array {
        /*
         * Resolve the branch directly from the current
         * WooCommerce package/cart contents.
         *
         * This avoids calling BranchResolver during
         * WooCommerce shipping-rate calculation.
         */
        $branch = $this->resolveBranchFromPackage($package);

        /*
         * If no branch can be safely identified,
         * do not modify WooCommerce's shipping rates.
         */
        if ($branch === null) {
            return $rates;
        }

        $branchSlug = $branch->slug();

        /*
         * Only explicitly mapped Massar branches
         * participate in Pickup filtering.
         */
        if (!isset(self::BRANCH_PICKUP_LOCATIONS[$branchSlug])) {
            return $rates;
        }

        $expectedPickupLocation =
            self::BRANCH_PICKUP_LOCATIONS[$branchSlug];

        $filteredRates = [];

        $hasPickupRates = false;
        $matchingPickupFound = false;

        foreach ($rates as $rateKey => $rate) {

            /*
             * Keep unexpected/non-standard values untouched.
             */
            if (!$rate instanceof WC_Shipping_Rate) {
                $filteredRates[$rateKey] = $rate;
                continue;
            }

            /*
             * Preserve every normal shipping method exactly
             * as WooCommerce generated it.
             */
            if ($rate->get_method_id() !== self::PICKUP_METHOD_ID) {
                $filteredRates[$rateKey] = $rate;
                continue;
            }

            /*
             * This is a WooCommerce Local Pickup rate.
             */
            $hasPickupRates = true;

            /*
             * WooCommerce stores the Pickup Location name
             * in the shipping rate metadata.
             */
            $pickupLocation = $rate->get_meta(
                'pickup_location',
                true
            );

            if (!is_string($pickupLocation)) {
                continue;
            }

            /*
             * Keep only the Pickup Location belonging
             * to the current Massar branch.
             */
            if ($pickupLocation === $expectedPickupLocation) {
                $filteredRates[$rateKey] = $rate;
                $matchingPickupFound = true;
            }
        }

        /*
         * No Pickup rates were generated.
         *
         * Leave WooCommerce's original rates untouched.
         */
        if (!$hasPickupRates) {
            return $rates;
        }

        /*
         * A Pickup rate existed, but we could not safely
         * identify the matching branch location.
         *
         * Never hide all Pickup locations in this case.
         */
        if (!$matchingPickupFound) {
            return $rates;
        }

        /*
         * Return all normal shipping methods plus the
         * Pickup Location belonging to the current branch.
         *
         * The original WC_Shipping_Rate object is preserved,
         * so cost and tax handling remain under WooCommerce.
         */
        return $filteredRates;
    }

    /**
     * Resolve the Massar branch from the current WooCommerce package.
     *
     * BranchCartManager already stores wcbm_branch_id on cart items,
     * and the cart is restricted to one branch.
     *
     * @param array<string, mixed> $package
     *
     * @return Branch|null
     */
    private function resolveBranchFromPackage(
        array $package
    ): ?Branch {
        if (
            isset($package['contents'])
            && is_array($package['contents'])
        ) {
            foreach ($package['contents'] as $cartItem) {

                if (!is_array($cartItem)) {
                    continue;
                }

                if (!isset($cartItem['wcbm_branch_id'])) {
                    continue;
                }

                $branchId = absint(
                    $cartItem['wcbm_branch_id']
                );

                if ($branchId <= 0) {
                    continue;
                }

                return $this->branchRepository->findById(
                    $branchId
                );
            }
        }

        /*
         * Some WooCommerce shipping calculations may not provide
         * package contents in the expected form.
         *
         * In that situation, safely fall back to the current cart.
         */
        if (
            function_exists('WC')
            && WC()->cart !== null
        ) {
            foreach (WC()->cart->get_cart() as $cartItem) {

                if (!is_array($cartItem)) {
                    continue;
                }

                if (!isset($cartItem['wcbm_branch_id'])) {
                    continue;
                }

                $branchId = absint(
                    $cartItem['wcbm_branch_id']
                );

                if ($branchId <= 0) {
                    continue;
                }

                return $this->branchRepository->findById(
                    $branchId
                );
            }
        }

        return null;
    }
}