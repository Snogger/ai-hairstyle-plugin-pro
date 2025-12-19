# AI Hairstyle Try-On Pro Plugin

Standalone WordPress plugin for AI hairstyle try-on and salon booking using Google Gemini API.

## Features
- Single-page AJAX wizard for photo uploads, hairstyle selection, color changes, and booking.
- AI generation with 4 angles (front/left/right/back).
- Custom booking calendar with next-available, deposit, coupons.
- Admin CPTs for hairstyles (auto-populated from assets) and staff.
- Analytics, emails, GDPR-compliant temp files.
- Elementor widget for unlock.

## Architecture Diagrams
- [Level 1 - System Context](docs/diagrams/level1.png)
- [Level 2 - Containers](docs/diagrams/level2.png)
- [Level 3 - Components](docs/diagrams/level3.png)

## Installation
1. Upload the folder to `/wp-content/plugins/`.
2. Activate in WordPress admin.
3. Add shortcode `[ai-hairstyle-tryon-pro]` to any page.

## Configuration
- Go to WordPress Admin > AI Hairstyle Try-On Pro.
- Set API key, salon info, colors, coupons, etc.

## Requirements
- WordPress 5.0+
- PHP 8.0+
- Google Gemini API key

## Final Admin Backend Reference (Locked & Complete)

This is the definitive guide to all admin tabs, CPTs, metaboxes, and their frontend ties.

### 1. Configuration Tab (Global Settings – Admin Tab)
- **Security**: Entire tab locked with username/password (staff cannot access). Override: Administrator role or developer credentials. Rescan Assets Button: Requires developer-only password popup.
- **Salon Information Metabox**:
  - Salon Name: Used in branding and social captions ("My new cut at [Salon Name]!").
  - Salon Logo: Used for branding and watermark (opacity slider 0.2 default + preview).
  - Primary Email: All booking emails (blind CC – customer never sees it). Staff replies use reply-to = primary.
  - Gender Selection: Radio (Male / Female / Both). Frontend: Tabs if "Both"; custom rename fields for buttons.
  - Operating Times: Weekly recurring hours + exclude UK/Scottish bank holidays + special closures. Frontend: Combined with staff availability.
- **Additional Information Metabox**:
  - Currency: Dropdown (GBP, USD, CAD, AUD, NZD, EUR, JPY) — symbol shown in prices.
  - Timezone: Dropdown.
  - Tax Settings: Checkbox + % input.
  - Thumbnail Preview Checkbox: "Show thumbnails in grid" (default yes).
- **Coupons/Offers Metabox**:
  - Haircut/Style Offers: Fixed/% discount, exclude hairstyle IDs (blank = all), start/end dates.
  - Upsell Products Offers: Fixed/% discount, exclude product IDs, start/end dates.
  - WooCommerce compatibility via hooks/filters.
- **Watermark Settings**: Integrated in Salon Info.
- **Rescan Assets Button**: Separate metabox, developer password popup.
- **Color Configuration**: Primary/secondary pickers (labelled: button background, text, etc.).

### 2. Hairstyles CPT (class-hairstyles-cpt.php)
- **Auto-Population** (activation + Rescan button): Scans assets/references/men/women, creates posts from folders, auto-sets gender, attaches gallery, length/merge variants (repeatable metaboxes). Never overwrites prices/enable/disable/SEO.
- **Metaboxes**:
  - Price per variant (repeatable) — overrides global.
  - Enable/Disable toggle.
  - SEO alternative names.
- **Frontend Relation**:
  - Grid/list in wizard Step 2 (gender-filtered, enabled posts).
  - Gallery for prompt references.
  - Thumbnail preview (Configuration checkbox).
  - Selected price displayed (with discount crossed-out + "Book today for [discounted]").
  - Length filter from variants.

### 3. Staff CPT (class-staff-cpt.php)
- **Manual Creation**.
- Featured image: Photo.
- Rich text: Bio (excerpt + "read more").
- Email field: Secondary (blind CC, reply-to = primary).
- **Metaboxes**:
  - Availability grid: Weekly hours + blocked dates/times.
  - External calendar link: URL field + setup instructions.
- **Frontend Relation**:
  - Stylist selection: Cards (photo, name, bio excerpt + "read more").
  - Next available: Calculates across staff (grid + external link).
  - "Next available": Auto-selects earliest (shows options, scroll dates/times).
  - Specific stylist: Shows only their slots.
  - Booking: Separate email to staff (reply-to = primary).

### 4. Analytics Tab (class-analytics.php)
- Charts/Totals: Generations, bookings, popular hairstyles/staff, API calls.
- Revenue: Weekly/monthly/annual + projected (booked hairstyles + deposit).
- Staff filter: Per-staff metrics.
- CSV export (no personal data).
- Business-savvy: Conversion rate, revenue estimate, peak times/days.

### Business-Savvy Additions (All Included)
- Deposit %/fixed (global).
- No-show/cancellation policy text (editable).
- Review request email toggle (1 day after).
- Hairstyles CPT: Category field + "Popular" flag (auto from analytics).
- Staff CPT: Commission % + "Specialty" tags.

## License
GPLv2 or later.