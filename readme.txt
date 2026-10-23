=== Ngiti Dental ===
Contributors: efraimray
Tags: one-column, custom-logo, custom-menu, featured-images, theme-options, translation-ready, accessibility-ready
Requires at least: 6.3
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A classic WordPress theme for dental clinics and professional practices, with an appointment request form, live opening hours and a tooth-chart service finder.

== Description ==

Ngiti Dental gives a small clinic a complete website in one theme:

* Page templates for About, Services, Team, Appointment Request, Patient FAQ, Contact, Privacy Notice and Disclaimer.
* Services and Team as custom post types, with duration, starting price (PHP peso), role, credentials and initials avatars.
* Appointment request and contact forms handled by the theme (nonce, honeypot, rate limit, sanitizing, wp_mail, saved private request posts with reference numbers).
* Clinic details and opening hours in Appearance > Customize > Clinic details.
* A live "Open now" badge that stays correct on cached pages.
* An FDI tooth chart that suggests services for the tapped tooth.
* Dentist JSON-LD, meta description and Open Graph tags (disabled automatically when Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework is active).
* Light and dark mode, skip link, visible focus states, self-hosted fonts, no jQuery.
* A one-click sample content importer.

All names, credentials and prices in the sample content are fictional.

== Installation ==

1. In wp-admin, go to Appearance > Themes > Add New > Upload Theme.
2. Choose ngiti-dental-theme.zip and click Install Now, then Activate.
3. Click "Create sample content" in the notice, or create pages and choose their templates yourself.
4. Open Appearance > Customize > Clinic details and enter the clinic's phone, address and hours.
5. Edit Services and Team in the admin menu.

== Frequently Asked Questions ==

= Where do appointment requests go? =

Each request is saved under Requests in wp-admin and emailed to the address in Customize > Clinic details (or the admin email). Use an SMTP plugin so the emails are delivered reliably.

= Can I place the form on another page? =

Yes. Use the shortcodes [ngiti_appointment_form] or [ngiti_contact_form].

= Does the theme load anything from other websites? =

No. Fonts, scripts and icons are bundled with the theme. The map is a text link.

= Why are post types in a theme? =

So a clinic gets everything in one install. The content stays in the database if you switch themes; copy inc/post-types.php into a small plugin to keep the admin screens.

== Changelog ==

= 1.0.0 - 2026-10-23 =
* First release.

== Resources ==

* Young Serif, Copyright 2023 The Young Serif Project Authors, SIL Open Font License 1.1, https://github.com/noirblancrouge/YoungSerif
* Figtree, Copyright 2022 The Figtree Project Authors, SIL Open Font License 1.1, https://github.com/erikdkennedy/figtree
* Icons and illustrations: original artwork by Efraim Ray, GPL-2.0-or-later.
* screenshot.png: screenshot of the theme with its own sample content, GPL-2.0-or-later.

== Copyright ==

Ngiti Dental WordPress Theme, Copyright 2026 Efraim Ray.
Ngiti Dental is distributed under the terms of the GNU GPL v2 or later.
