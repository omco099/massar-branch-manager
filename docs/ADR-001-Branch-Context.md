# ADR-001

## Title

Branch Context Resolution

## Status

Accepted

## Problem

The plugin must always know which branch is currently active.

Every component depends on this information:

- Product Query
- Pricing
- Inventory
- Cart
- Checkout
- Orders
- Reports

The branch must be resolved only once.

## Decision

The active branch will be resolved by a dedicated Branch Context Service.

No other class may determine the current branch.

All modules must request the branch only through BranchContext.

## Consequences

Single source of truth.

Easy testing.

Easy replacement in future.

No duplicated logic.