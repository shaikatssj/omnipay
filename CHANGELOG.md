# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-06-22

### Added
- **Store White-Labeling (Checkout Layout)**:
  - Added option to toggle the alignment of the checkout page layout (swapping "Select Payment Method" and "Payment Request" columns) from the store settings.
  - Added `checkout_layout` column to the `stores` table via database migration.
- **Refined Checkout Design**:
  - Transitioned the checkout page to an ultra-clean, minimalist, Stripe-like aesthetic.
  - Widened the checkout container to 1050px and increased internal padding for a more spacious, professional layout.

### Fixed
- **Dashboard QR-Codes**: Made the QR-codes dashboard page grid fully responsive.

## [Unreleased] - 2026-06-21

### Added
- **No-Code Merchant Features**: Merchants can now create static payment links without writing code.
- **Payment Links Table**: New `payment_links` table and model to manage donation links or fixed-price URLs.
- **Embeddable Buy Buttons**: Merchants can copy HTML snippets from the dashboard to embed a "Pay Now" button on any site.
- **Store White-Labeling**:
  - Store owners can pick a custom primary theme color.
  - Store owners can hide the "Powered by OmniPay" branding.
  - Store owners can inject custom CSS directly into the checkout layout to override styles completely.

### Fixed
- Added the missing "Payment Links" navigation item to the dashboard sidebar.
- Completely overhauled the CSS and layout of the Payment Links dashboard page to match the application's custom design system.
- Fixed fatal redeclaration error of `editStore` in `DashboardController` by merging update logic into the existing `updateStore` method.
