# Changelog

All notable changes to this project will be documented in this file.

## [1.5.3] - 2026-06-25

### Fixed
- Vastly improved the UI design of the Email Templates management pages to match the premium dashboard layout.

## [1.5.2] - 2026-06-25

### Fixed
- Fixed an issue where email templates were not automatically seeded when upgrading via the UI Updater.

## [1.5.1] - 2026-06-25

### Added
- **Editable Email Templates**: Admins can now view and edit all system email templates (Invoice Created, Invoice Paid, Login Notifications, 2FA, etc.) directly from the dashboard UI.
- Included dynamic variables for email subjects and bodies.
- Refactored local MFS gateways to use database-driven email templates for manual verification notifications.

## [1.5.0] - 2026-06-25

### Added
- **Role-Based Access Control (RBAC) & Staff Management**: Added `store_user` pivot table and UI to allow store owners to invite staff (Managers, Cashiers, Accountants).
- **Advanced Analytics & Reporting**: Implemented a new dashboard featuring total volume, transaction counts, success rates, interactive revenue charts (Chart.js), and CSV export capabilities.
- **Official Developer SDKs**: Created the `omnipay-php` package, a Guzzle-based PHP wrapper to easily integrate with the OmniPay API.
- **Global Gateways Integration**: Added robust built-in support for Stripe, PayPal, and Razorpay payment drivers.

### Changed
- **UI Enhancements**: 
  - Modernized the Staff Management page with a sleek, blurred backdrop modal.
  - Fixed navigation sidebar icon alignments.
  - Polished the Manage Stores dashboard UI (wider cards, better flex wrapping for actions, refined typography).

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
