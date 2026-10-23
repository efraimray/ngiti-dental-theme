# Changelog

All notable changes to the Ngiti Dental theme are documented here. This project uses [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-23

### Added
- Classic WordPress theme for dental clinics and professional practices (no page builder, no jQuery).
- Eight page templates: About, Services, Team, Appointment Request, Patient FAQ, Contact, Privacy Notice (RA 10173) and Disclaimer, plus front page, default page, single, index and 404 templates.
- Custom post types: Services (duration, starting price in pesos, icon, tooth groups), Team members (role, credentials, initials avatar, colour) and private Appointment requests.
- Appointment request and contact forms handled by the theme: nonce, honeypot, 60-second rate limit, sanitizing and escaping, PH mobile number and closed-day validation, saved request post, `wp_mail()` notice, reference number and Post/Redirect/Get success state. Shortcodes `[ngiti_appointment_form]` and `[ngiti_contact_form]`.
- Customizer section "Clinic details": phone, Messenger, emails, address, map link, weekday/Saturday/Sunday hours and social links.
- Live "Open now / Closing soon / Closed" badge computed in the browser from the clinic schedule (cache-safe).
- FDI tooth finder on the Services page.
- Dentist (LocalBusiness) JSON-LD, meta description and Open Graph tags that step aside when an SEO plugin is active.
- Light and dark mode with a remembered toggle, skip link, focus styles, self-hosted fonts.
- One-click sample content importer (pages with templates, services, team, menus).
- Static HTML export in `demo/` for GitHub Pages, generated from the running theme by `tools/export-static.py`.
- Translation template `languages/ngiti-dental-theme.pot`.
- One-click GitHub publisher (`publish-to-github.bat`).
