<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Shipping;

use Alnaseeg\BranchManager\Branch\BranchResolver;
use WC_Shipping_Rate;

/**
 * Filters WooCommerce Local Pickup rates according to
 * the currently active Massar branch.
 */
final class BranchPickupFilter
{
    /**
     * WooCommerce Local Pickup method ID.
     */
    private const PICKUP_METHOD_ID = 'pickup_location';

    /**
     * Map Massar branch slugs to WooCommerce pickup location names.
     *
     * The WooCommerce pickup rate index is intentionally not used here.
     *
     * @var array<string,string>
     */
    private const BRANCH_PICKUP_LOCATIONS = [
        'mall-of-oman' => 'مول عمان',
        'nizwa'       => 'نزوى',
        'al-arimi'    => 'العريمي',
    ];

    public function __construct(
        private readonly BranchResolver $branchResolver
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
     * Filter Local Pickup rates according to the current branch.
     *
     * Normal shipping methods are always preserved.
     *
     * If the current branch cannot be resolved, or the matching
     * pickup location cannot be found, the original rates are returned.
     *
     * @param array<string,WC_Shipping_Rate> $rates
     * @param array<string,mixed>            $package
     *
     * @return array<string,WC_Shipping_Rate>
     */
    public function filterRates(
        array $rates,
        array $package
    ): array {
        unset($package);

        /*
         * Resolve the branch using the existing Massar
         * BranchResolver.
         *
         * We do not create another branch/session mechanism.
         */
        $branch = $this->branchResolver->current();

        if ($branch === null) {
            return $rates;
        }

        $branchSlug = $branch->slug();

        /*
         * Only branches explicitly mapped to a Pickup location
         * participate in this filter.
         */
        if (!isset(self::BRANCH_PICKUP_LOCATIONS[$branchSlug])) {
            return $rates;
        }

        $expectedPickupLocation = self::BRANCH_PICKUP_LOCATIONS[$branchSlug];

        /*
         * First verify that WooCommerce actually has the expected
         * Pickup Location configured and enabled.
         *
         * This gives us a safe failure mode:
         * if matching fails, we leave all rates untouched.
         */
        if (!$this->hasConfiguredPickupLocation($expectedPickupLocation)) {
            return $rates;
        }

        $filteredRates = [];
        $matchingPickupFound = false;

        foreach ($rates as $rateKey => $rate) {
            if (!$rate instanceof WC_Shipping_Rate) {
                $filteredRates[$rateKey] = $rate;
                continue;
            }

            /*
             * Keep every non-Pickup shipping method exactly as it is.
             */
            if ($rate->get_method_id() !== self::PICKUP_METHOD_ID) {
                $filteredRates[$rateKey] = $rate;
                continue;
            }

            /*
             * This is a Pickup rate.
             *
             * WooCommerce stores the actual Pickup Location name
             * in the rate metadata.
             */
            $pickupLocation = (string) $rate->get_meta(
                'pickup_location',
                true
            );

            /*
             * Keep only the Pickup Location belonging to
             * the currently active Massar branch.
             */
            if ($pickupLocation === $expectedPickupLocation) {
                $filteredRates[$rateKey] = $rate;
                $matchingPickupFound = true;
            }
        }

        /*
         * Safety rule:
         *
         * Never remove all Pickup rates if the expected matching
         * Pickup rate could not be identified.
         *
         * In that case WooCommerce receives the original rates.
         */
        if (!$matchingPickupFound) {
            return $rates;
        }

        return $filteredRates;
    }

    /**
     * Check whether the expected WooCommerce Pickup Location
     * exists and is enabled.
     */
    private function hasConfiguredPickupLocation(
        string $expectedLocation
    ): bool {
        $locations = get_option(
            'pickup_location_pickup_locations',
            []
        );

        if (!is_array($locations)) {
            return false;
        }

        foreach ($locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            $name = isset($location['name'])
                ? (string) $location['name']
                : '';

            if ($name !== $expectedLocation) {
                continue;
            }

            /*
             * WooCommerce stores enabled as a boolean in the
             * current Local Pickup configuration.
             */
            return !isset($location['enabled'])
                || wc_string_to_bool($location['enabled']);
        }

        return false;
    }
}