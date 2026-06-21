# Changelog

All notable changes to this project will be documented in this file.

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
