# Ngiti Dental Tutorial: How the Theme Works

This guide walks through the theme file by file. You need basic HTML and a little PHP; everything else is explained.

## 1. Architecture in one picture

```
            Customizer (phone, address, hours)        sample-content.json
                         │                                   │
                         ▼                                   ▼
 functions.php ──► inc/template-tags.php  ◄──── inc/sample-content.php (importer)
   │  enqueue          ngiti_opt(), ngiti_week(),
   │  assets           ngiti_get_services(), cards, icons
   │                         │
   ▼                         ▼
 header.php ─ front-page.php / page-templates/*.php ─ footer.php
                         │
          form posts to  ▼
   wp-admin/admin-post.php?action=ngiti_request
                         │
          inc/appointment-form.php: nonce → honeypot → validate → rate limit
                         │
          save private "Request" post + wp_mail() → redirect with ?ngiti_ref=
                         │
 assets/js/theme.js: menu, dark mode, open-now badge, validation, tooth finder
```

The key idea: **every clinic detail is entered once** (Customizer or post types) and reused by the header, footer, sticky buttons, hours table, live badge and JSON-LD.

## 2. The main files

### `functions.php` (PHP)
Defines `NGITI_VERSION`, loads the six `inc/` files, registers theme supports (`title-tag`, `custom-logo`, `post-thumbnails`, menus), enqueues `fonts.css`, `main.css` and `theme.js` (deferred), and passes translated strings to JavaScript with `wp_localize_script()`. A tiny inline script in `wp_head` applies the saved light/dark choice before the page paints.

### `inc/template-tags.php` (PHP)
The toolbox the templates call:

- `ngiti_opt( 'phone' )` reads a Customizer value, falling back to the sample JSON.
- `ngiti_parse_hours( '09:00-12:00,13:00-17:00' )` turns text into ranges; `ngiti_week()` builds Monday–Sunday.
- `ngiti_format_time( '12:00' )` returns `12:00 NN`, the Philippine way of writing noon.
- `ngiti_open_status()` prints the badge with the schedule in a `data-ngiti-hours` attribute; JavaScript fills in the state.
- `ngiti_get_services()` / `ngiti_get_team()` return post-type entries, or the sample JSON when none exist yet, so a fresh install never looks empty.
- `ngiti_icon( 'phone' )` returns small inline SVG icons (generic shapes, no brand logos).

### `inc/customizer.php` (PHP)
Adds the **Clinic details** section. Every setting has a `sanitize_callback`: `sanitize_text_field`, `sanitize_email`, `esc_url_raw`, or `ngiti_sanitize_hours`, which keeps only valid `HH:MM-HH:MM` ranges.

### `inc/post-types.php` (PHP)
Registers `ngiti_service` (public), `ngiti_team` (admin only) and `ngiti_request` (private, cannot be created by hand). The "Details" meta box saves duration, price, icon, tooth groups, role, credentials, initials and colour, with a nonce check, `current_user_can()` and per-field sanitizing (`absint` for prices, `sanitize_hex_color` for colours, an allow-list for tooth groups).

### `inc/appointment-form.php` (PHP + HTML)
`ngiti_render_form( 'appointment' )` prints the form; `ngiti_handle_request()` processes it:

1. `wp_verify_nonce()` stops forged submissions.
2. A hidden **honeypot** field catches bots.
3. Every field is sanitized, then validated: name, PH mobile (`ngiti_normalize_mobile()` accepts `0917 555 0142` and `+63 917 555 0142`), optional email, a service from the real list, and a date that is tomorrow or later, within 120 days, on a day the clinic is open.
4. A transient keyed by a hash of the IP allows one request per minute.
5. The request is saved as a private post, emailed with `wp_mail()`, and the visitor is redirected back with `?ngiti_status=sent&ngiti_ref=NGT-…` (Post/Redirect/Get, so refreshing never sends twice).

### `inc/seo.php` (PHP)
Prints a meta description, Open Graph tags and a `Dentist` JSON-LD block built from the Customizer, including `openingHoursSpecification` for each range. If an SEO plugin is active, the meta and OG tags are skipped.

### `inc/sample-content.php` (PHP)
Shows an admin notice after activation. "Create sample content" makes the pages (with `_wp_page_template` set), the services, the team and two menus. It skips anything whose slug already exists, so running it twice is safe.

### Templates (PHP + HTML)
`header.php` and `footer.php` wrap every page. `front-page.php` builds the home page from template parts. Each file in `page-templates/` starts with `Template Name:` so it appears in the page editor's Template menu.

### `assets/js/theme.js` (JavaScript)
One IIFE with five parts: theme toggle (localStorage in `try/catch`), mobile menu (Escape closes it), open-now status (`computeStatus()` is a pure function that compares the current minute in the clinic's timezone with today's ranges and finds the next opening), form validation (same rules as PHP, shown inline with `aria-invalid`), and the tooth finder (`describeTooth()` maps FDI numbers like 46 to "Lower right first molar").

### `assets/css/main.css` (CSS)
Starts with the design tokens: colours, two font families, a type scale and spacing. Dark mode only redefines tokens, under `prefers-color-scheme` and `[data-theme="dark"]`, so no component has hard-coded colours.

## 3. Key concepts

- **Sanitize on input, escape on output**: data is cleaned when saved (`sanitize_*`) and escaped when printed (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- **Nonces** prove that a form came from your own site.
- **Cache-safe dynamic content**: the server prints the schedule, the browser decides "open" or "closed". A page cached at noon is still correct at 7 PM.
- **FDI tooth numbering**: the first digit is the quadrant (1 upper right, 2 upper left, 3 lower left, 4 lower right), the second is the tooth from the front (1 central incisor … 8 wisdom tooth).
- **Progressive enhancement**: forms work without JavaScript (the server validates everything); JavaScript only makes errors appear sooner.

## 4. The static demo

`tools/export-static.py` downloads each page from a running WordPress site with the theme active, rewrites links to `about.html`-style files, removes WordPress-only tags, and switches `theme.js` into demo mode (`ngitiTheme.demo = true`), where forms show the success state without sending anything.

## 5. Extension exercises

1. **Holiday closures**: add a Customizer textarea of dates (`2026-12-25`), skip those days in `ngiti_valid_date()` and in `computeStatus()`, and add `specialOpeningHoursSpecification` to the JSON-LD.
2. **Google Sheets log**: hook `ngiti_request_received` and send the lines to a Google Apps Script web app with `wp_remote_post()`.
3. **Veterinary version**: change the schema type with the `ngiti_schema` filter to `VeterinaryCare`, rename the Team labels, and replace the tooth finder with a pet-type picker.
