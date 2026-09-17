<?php

declare(strict_types=1);

namespace Alnaseeg\BranchManager\Shipping;

use Alnaseeg\BranchManager\Branch\BranchRepository;

/**
 * Controls WooCommerce Local Pickup rates according to
 * the current Massar branch.
 *
 * WooCommerce 11.x creates Local Pickup rates through
 * WC_Shipping_Method::add_rate().
 *
 * This class filters the rate arguments before WooCommerce
 * creates the WC_Shipping_Rate object.
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
     * The numeric pickup location index is intentionally
     * NOT used as branch logic.
     *
     * @var array<string, string>
     */
    private const BRANCH_PICKUP_LOCATIONS = [
        'mall-of-oman' => 'مول عمان',
        'nizwa'       => 'نزوى',
        'al-arimi'    => 'العريمي',
    ];

    /**
     * Cached current branch ID for this request.
     */
    private ?int $branchId = null;

    /**
     * Whether the branch ID has already been resolved.
     */
    private bool $branchResolved = false;

    /**
     * Cached expected WooCommerce Pickup Location.
     */
    private ?string $expectedPickupLocation = null;

    /**
     * Whether the expected Pickup Location was verified.
     */
    private bool $pickupLocationVerified = false;

    /**
     * Whether the expected Pickup Location exists and is enabled.
     */
    private bool $pickupLocationAvailable = false;

    public function __construct(
        private readonly BranchRepository $branchRepository
    ) {
    }

    /**
     * Register WooCommerce hooks.
     *
     * The filter is intentionally registered on
     * woocommerce_shipping_method_add_rate_args.
     *
     * This runs before WooCommerce creates the shipping rate.
     */
    public function register(): void
    {
        add_filter(
            'woocommerce_shipping_method_add_rate_args',
            [$this, 'filterRateArguments'],
            20,
            2
        );
    }

    /**
     * Filter shipping rate arguments before WooCommerce
     * creates the WC_Shipping_Rate object.
     *
     * Normal shipping methods are returned untouched.
     *
     * For WooCommerce Local Pickup:
     *
     * - Keep the Pickup Location belonging to the
     *   current Massar branch.
     * - Prevent other Pickup Locations from being created.
     *
     * If the current branch cannot be resolved, or the expected
     * Pickup Location is not configured/enabled, all rate arguments
     * are returned unchanged as a safety measure.
     *
     * @param array<string,mixed> $args
     * @param mixed                $shippingMethod
     *
     * @return array<string,mixed>
     */
    public function filterRateArguments(
        array $args,
        mixed $shippingMethod
    ): array {
        unset($shippingMethod);

        /*
         * Only inspect WooCommerce's new Local Pickup rates.
         *
         * Example:
         * pickup_location:0
         * pickup_location:1
         * pickup_location:2
         */
        $rateId = isset($args['id'])
            ? (string) $args['id']
            : '';

        if (!$this->isPickupRate($rateId)) {
            return $args;
        }

        /*
         * The Local Pickup implementation supplies the actual
         * location name in the rate metadata.
         */
        $pickupLocation = '';

        if (
            isset($args['meta_data'])
            && is_array($args['meta_data'])
            && isset($args['meta_data']['pickup_location'])
        ) {
            $pickupLocation = (string) $args['meta_data']['pickup_location'];
        }

        /*
         * If WooCommerce did not provide the expected Pickup
         * Location metadata, do not interfere with the rate.
         */
        if ($pickupLocation === '') {
            return $args;
        }

        /*
         * Resolve the Massar branch from the actual shipping
         * package/cart contents.
         *
         * We intentionally do NOT call BranchResolver here.
         *
         * Shipping-rate calculation must remain independent
         * from PageBranchResolver and branch-page resolution.
         */
        $branchId = $this->resolveBranchIdFromPackage(
            $args['package'] ?? []
        );

        /*
         * If no branch can safely be identified, leave all
         * WooCommerce Pickup rates untouched.
         */
        if ($branchId === null) {
            return $args;
        }

        /*
         * Resolve the Massar branch from the existing repository.
         */
        $branch = $this->branchRepository->findById(
            $branchId
        );

        if ($branch === null || !$branch->isActive()) {
            return $args;
        }

        $branchSlug = $branch->slug();

        /*
         * Only explicitly mapped Massar branches participate
         * in Pickup filtering.
         */
        if (!isset(self::BRANCH_PICKUP_LOCATIONS[$branchSlug])) {
            return $args;
        }

        $this->expectedPickupLocation =
            self::BRANCH_PICKUP_LOCATIONS[$branchSlug];

        /*
         * Verify that the Pickup Location expected by Massar
         * actually exists and is enabled in WooCommerce.
         *
         * If it does not, keep WooCommerce's original behavior
         * instead of hiding every Pickup Location.
         */
        if (!$this->isExpectedPickupLocationAvailable()) {
            return $args;
        }

        /*
         * This is the Pickup Location belonging to the current
         * Massar branch.
         *
         * Return the original arguments untouched.
         *
         * WooCommerce remains responsible for:
         * - cost
         * - tax
         * - pickup address
         * - pickup details
         * - rate creation
         */
        if ($pickupLocation === $this->expectedPickupLocation) {
            return $args;
        }

        /*
         * This Pickup Location belongs to another Massar branch.
         *
         * WC_Shipping_Method::add_rate() requires both an ID and
         * a label. When either is empty, WooCommerce stops before
         * creating the WC_Shipping_Rate object.
         *
         * This prevents the unwanted Pickup Location from entering
         * the package rates at all.
         */
        $args['id'] = '';
        $args['label'] = '';

        return $args;
    }

    /**
     * Determine whether a rate belongs to WooCommerce's
     * new Local Pickup method.
     */
    private function isPickupRate(string $rateId): bool
    {
        return str_starts_with(
            $rateId,
            self::PICKUP_METHOD_ID . ':'
        );
    }

    /**
     * Resolve the Massar branch ID from the shipping package.
     *
     * BranchCartManager and ProductBranchManager already store
     * wcbm_branch_id on each cart item.
     *
     * WooCommerce passes the same package into
     * WC_Shipping_Method::add_rate() through the "package"
     * rate argument.
     *
     * @param mixed $package
     *
     * @return int|null
     */
    private function resolveBranchIdFromPackage(
        mixed $package
    ): ?int {
        if ($this->branchResolved) {
            return $this->branchId;
        }

        $this->branchResolved = true;

        if (!is_array($package)) {
            return null;
        }

        if (
            !isset($package['contents'])
            || !is_array($package['contents'])
        ) {
            return null;
        }

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

            $this->branchId = $branchId;

            return $this->branchId;
        }

        return null;
    }

    /**
     * Verify that the Pickup Location expected by the
     * current Massar branch exists and is enabled.
     *
     * WooCommerce stores the new Local Pickup locations in:
     *
     * pickup_location_pickup_locations
     */
    private function isExpectedPickupLocationAvailable(): bool
    {
        if ($this->pickupLocationVerified) {
            return $this->pickupLocationAvailable;
        }

        $this->pickupLocationVerified = true;

        if ($this->expectedPickupLocation === null) {
            return false;
        }

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

            if ($name !== $this->expectedPickupLocation) {
                continue;
            }

            /*
             * WooCommerce stores enabled as a boolean/string
             * depending on how the settings were saved.
             */
            $enabled = isset($location['enabled'])
                ? wc_string_to_bool($location['enabled'])
                : false;

            $this->pickupLocationAvailable = $enabled;

            return $this->pickupLocationAvailable;
        }

        return false;
    }
}