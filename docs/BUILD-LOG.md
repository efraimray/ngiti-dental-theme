# How Ngiti Dental Was Built

This is the plain-language record of how the Ngiti Dental WordPress theme was planned, written, tested and packaged, in the order it happened.

| | |
|---|---|
| **Project** | Ngiti Dental — WordPress theme for a dental clinic (sample: Ngiti Dental Studio, Malaybalay City, Bukidnon) |
| **Type** | Friday website, week 4: Professional practice website (custom classic WordPress theme) |
| **Version** | 1.0.0 |
| **Built** | 2026-10-23, by Claude (nightly showcase run) for Efraim Ray |
| **Difficulty** | Intermediate–advanced (WordPress theme APIs, secure form handling, custom post types) |
| **Time to install/run** | About 5 minutes: upload the zip, activate, click "Create sample content", fill in Clinic details |

## 1. Languages Used

Line counts are real (`find . -name "*.<ext>" | xargs cat | wc -l`), excluding `demo/` (generated) and the publish-to-github files.

| Language | Lines | Share | Files | What it does here |
|---|---:|---:|---|---|
| PHP | 2,343 | 63.9% | 26 files: `functions.php`, `header.php`, `footer.php`, `front-page.php`, `page.php`, `single.php`, `index.php`, `404.php`, 8 page templates, 4 template parts, 6 `inc/` files | The theme itself: templates, Customizer settings, custom post types, the appointment form handler, SEO tags and the sample content importer |
| CSS | 482 | 13.2% | `style.css` (theme header), `assets/css/main.css`, `assets/css/fonts.css` | The design system: colour/type/spacing tokens, light and dark themes, layout, responsive rules |
| JavaScript | 460 | 12.6% | `assets/js/theme.js`, `tests/site.test.js` | Mobile menu, dark-mode toggle, open-now badge, form validation, tooth finder; plus the Playwright test suite |
| Python | 178 | 4.9% | `tools/export-static.py`, `tools/arch_svg.py` | Exports the running theme to static HTML; draws the dental-arch illustration |
| JSON | 169 | 4.6% | `inc/sample-content.json` | One shared list of the sample clinic, services, team, FAQ and page texts |
| HTML | 33 | 0.9% | `tools/about-this-project.html` | Content of the showcase-only "About this project" page |
| **Total** | **3,665** | 100% | | |

**In one sentence:** PHP builds every page and handles the appointment requests, CSS gives the clinic its look in light and dark mode, JavaScript makes the menu, badge, form checks and tooth chart interactive, and Python and JSON feed the sample content and produce the static demo.

### Frameworks and APIs

- WordPress 6.3+ theme APIs: template hierarchy, page templates (`Template Name:`), `get_template_part()`, `wp_nav_menu()`, custom logo.
- WordPress Customizer API (`customize_register`, settings with sanitize callbacks).
- Custom post types and meta boxes (`register_post_type`, `add_meta_box`, `save_post`).
- `admin-post.php` form handling, nonces (`wp_nonce_field`, `wp_verify_nonce`), transients (rate limit), `wp_mail()`, `wp_safe_redirect()`.
- Shortcode API (`[ngiti_appointment_form]`, `[ngiti_contact_form]`).
- Browser `Intl.DateTimeFormat` (clinic time in Asia/Manila), `localStorage` (theme choice, in try/catch).
- Schema.org `Dentist` JSON-LD with `openingHoursSpecification`.
- No frameworks, no jQuery, no CDN. **No build step, npm or Composer is needed** to use the theme.

### Tools used to build and test

| Tool | Used for |
|---|---|
| `php -l` (PHP 8.3.6) | Syntax check of all 26 PHP files: 0 errors |
| WordPress 6.5.5 + SQLite Database Integration (from the WordPress Playground npm package) + `php -S` | A real WordPress install to run and test the theme |
| WP-CLI 2.12.0 | Installing WordPress, activating the theme, running the importer, `wp i18n make-pot` |
| `node --check` (Node 22) | Syntax check of `theme.js`, the test file and the Blogger post script |
| Playwright + Chromium | End-to-end tests on WordPress and on the static demo, screenshots, featured image rendering |
| curl | Server-side form tests that bypass the browser |
| Python 3 + Pillow | Static export, arch illustration, JPG conversion of the featured image |
| PowerShell 7.4.6 (Linux) | Testing `publish-to-github.ps1` with a stand-in `gh` and a local bare Git repository |
| zip | Packaging the deliverable |
| GitHub CLI + `publish-to-github.bat` | Publishing (run by Efraim on Windows) |

## 2. How It Works (the 30-second version)

```
PAGE MAP
  Home ─┬─ About ─ Services & Fees (+ tooth finder) ─ Our Dentists ─ Patient FAQ ─ Contact
        ├─ Request an Appointment ── success page with reference number
        └─ Footer: Privacy Notice (RA 10173) · Disclaimer · sticky Call / Messenger buttons

MAIN FLOW (WordPress)
  Patient fills form ─► JS checks fields ─► POST admin-post.php?action=ngiti_request
     ─► nonce ✓ honeypot ✓ sanitize ✓ validate (PH mobile, service, open day) ✓ rate limit ✓
     ─► save private "Request" post ─► wp_mail() to clinic ─► redirect ?ngiti_status=sent&ngiti_ref=NGT-…
```

The key idea is that the clinic's details are typed once (Customizer for phone, address and hours; post types for services and team) and every part of the site reads them. The open-now badge is calculated in the visitor's browser from the printed schedule, so cached pages stay correct.

## 3. Step-by-Step: How We Built It

### Step 1: Checked the day, GitHub access and earlier projects
Today is Friday 2026-10-23 in Asia/Manila. Week of month = ceil(23 / 7) = 4, so tonight's build is a **Professional practice website as a custom WordPress theme**. `gh api user` returned `efraimray`; listing repositories and reading `claude-improve` issues were refused (this session is bound to pre-connected repositories, and GitHub's issue page is blocked for fetching), so the improvement step was skipped. The artifact gallery showed 13 earlier items (Bukas Hours, PilaBoard, Tinta Color Studio, Kahera POS, Sinag, Tatak Schema, Abiso Advisories, Presyo Menu Board, Hulog Paluwagan Planner, Linaw A11y Checker and others); none is a clinic website or theme, and no earlier Friday website exists, so no niche is repeated.

### Step 2: Picked the niche and the name
A **dental clinic** is the most common professional practice in Philippine towns and benefits most from online booking. Name: **Ngiti Dental Studio** ("ngiti" = smile), set in Malaybalay City, Bukidnon, with an invented address, phone (0917 555 0142), team and prices, all marked as samples. Repo slug: `ngiti-dental-theme`, text domain `ngiti-dental-theme`.

### Step 3: Planned the content as data (JSON)
`inc/sample-content.json` holds the clinic details, 8 services (duration, starting price in ₱, icon, which tooth types they apply to), 4 team members (initials avatars, colours, "sample credential"), 6 patient FAQs and the About, Privacy and Disclaimer texts. Both the PHP importer and the static export read this one file.

### Step 4: Set up a real WordPress to test on
wordpress.org and GitHub archive downloads are blocked in the sandbox, so WordPress 6.5.5 and the SQLite plugin were taken from the `@wp-playground/wordpress-builds` npm package, installed with WP-CLI and served with `php -S 127.0.0.1:8088`. A test-only must-use plugin wrote outgoing emails to `mail.log` instead of sending them. (The Playground build leaves out three core CSS files, which show as 404s in the test site only.)

### Step 5: Wrote the design system (CSS)
`assets/css/main.css` starts with tokens: a cool mint-white paper, deep teal ink, "molar teal" `#0a6b66` for actions and a soft "gum blush" for highlights, with a complete dark set. Type pairs **Young Serif** (headings) with **Figtree** (text), self-hosted in `assets/fonts` (OFL) with latin-ext files so the ₱ sign renders. Layout: one calm column, rounded tooth-like shapes, and small FDI tooth numbers as the recurring detail.

### Step 6: Theme bootstrap (PHP: `style.css`, `functions.php`)
Theme header, supports (title tag, custom logo, thumbnails, menus, editor styles), local asset loading with `defer`, translated strings passed to JS, and a one-line head script that applies the saved dark/light choice before paint.

### Step 7: Helpers, Customizer and post types (PHP: `inc/`)
- `template-tags.php`: `ngiti_opt()`, hours parsing and formatting (`12:00 NN` for noon), the open-status markup, SVG icons and logo, service/team cards, page hero.
- `customizer.php`: the **Clinic details** section with 13 sanitized settings.
- `post-types.php`: Services, Team and private Requests, with a nonce-checked meta box and per-field sanitizing.

### Step 8: The appointment and contact forms (PHP + HTML)
`appointment-form.php` renders both forms and handles submissions: nonce, honeypot, sanitize, validate, rate limit, save, email, redirect with a reference like `NGT-261023-R2LL`. Two shortcodes expose the forms anywhere.

### Step 9: SEO and the sample-content importer (PHP)
`seo.php` prints the meta description, Open Graph tags and Dentist JSON-LD (skipped when a major SEO plugin is active). `sample-content.php` adds the "Create sample content" admin notice and creates 9 pages with templates, 8 services, 4 team members and two menus.

### Step 10: Templates (PHP + HTML)
`header.php`, `footer.php`, `front-page.php`, `page.php`, `single.php`, `index.php`, `404.php`, eight page templates and four template parts. The hero illustration (an upper dental arch with FDI numbers) is generated by `tools/arch_svg.py` and uses the theme's colour tokens so it works in dark mode.

### Step 11: Behaviour (JavaScript: `assets/js/theme.js`)
Menu, theme toggle, `computeStatus()` for the open-now badge, validators matching the PHP rules, a demo mode for the static export, and `describeTooth()` for the tooth finder.

### Step 12: First render and fixes
The first screenshot showed four problems, all fixed: the gum band of the arch drew only half a ring (redrawn as a thick line through the tooth centres, teeth enlarged); the clock icons inside price chips had no size and rendered huge (`.chip svg` sized); WordPress's default widgets (Archives, Categories) appeared in a footer widget area (widget area removed); the hero note card covered the arch label (moved below the arch).

### Step 13: Static export (Python: `tools/export-static.py`)
Instead of rebuilding the pages by hand, the script downloads every page from the running WordPress site, rewrites links to `about.html`-style files, removes WordPress-only tags, sets absolute GitHub Pages URLs for canonical and Open Graph tags, switches `theme.js` to demo mode and writes `sitemap.xml` and `robots.txt`. Result: 11 pages in `demo/`.

### Step 14: Testing
`tests/site.test.js` checks every page at 1280 px and 390 px in light and dark (no sideways scroll, no JS errors, no failed requests), all internal links, the mobile menu, the theme toggle, 14 logic cases, the appointment flow, the contact flow and the tooth finder.

| Test run | Result |
|---|---|
| Static demo (`demo/`, 11 pages × 4 views + flows) | **66/66 passed** |
| Live WordPress 6.5.5 (10 pages × 4 views + flows, real form posts) | **62/62 passed** after two fixes below |
| `php -l` on 26 PHP files | 0 errors |
| `node --check` on theme.js and the test file | OK |

Selected logic cases (all passed): Monday 10:00 → open; 11:45 → closing soon; 12:20 → closed, opens 1:00 PM; Saturday 13:30 → next opening Monday 9:00 AM; Sunday → opens tomorrow; `0917 555 0142` and `+63 917-555-0142` accepted, `02 8123 4567` rejected; tooth 16 → "Upper right first molar"; tooth 38 → wisdom tooth.

Server-side checks with curl (bypassing JavaScript): bad nonce → `nonce` error; honeypot filled → `spam`; bad mobile/email/unknown service/Sunday/no consent → all five flagged; past date → `date`; `+63` number → accepted and saved; a second request within a minute → `wait`; a foreign `ngiti_return` URL → redirected to the home page instead; HTML in the error and reference parameters is not printed.

Problems found and fixed during testing:
1. **Service names with "&" failed validation on WordPress**: `get_the_title()` returns `Check-up &#038; Cleaning`, while the form posts `Check-up & Cleaning`. Fixed by using the raw `post_title`.
2. **The rate limit blocked people fixing typos**: it ran before validation. Moved after validation, so only successful requests start the one-minute wait.
3. Radio "pills" are styled labels over hidden inputs, so the test now uses `check()` on the input.
4. The static demo had the export day baked into the date field's `min`; `theme.js` now always sets it to tomorrow.
5. The test ignored nothing at first and reported the Playground build's three missing core CSS files; those (and only those) are now excluded, with the reason in a comment.

Emails captured in `mail.log` showed the expected subject (`[Ngiti Dental Studio] New request NGT-…`), all fields, and a `Reply-To` header when the patient gave an email. Requests appeared as private posts under **Requests**.

### Step 15: Measurements
| Measure | Value |
|---|---|
| `main.css` + `fonts.css` gzipped | 7,170 bytes (7.0 KB) |
| `theme.js` gzipped | 5,292 bytes (5.2 KB) |
| Fonts (8 woff2 files, only the needed ones load) | 97 KB total on disk |
| External requests | 0 |
| Pages in the static demo | 11 |
| Translatable strings (`wp i18n make-pot`) | 231 |

Lighthouse was not run.

### Step 16: Translations and screenshot
`languages/ngiti-dental-theme.pot` was generated with WP-CLI; translator comments were added for the strings with placeholders after WP-CLI warned about them. `screenshot.png` (1200×900) is a capture of the theme's own home page.

### Step 17: Docs
README.md, docs/TUTORIAL.md, docs/DEPLOYMENT.md, readme.txt (WordPress.org format), CHANGELOG.md, LICENSE (GPL-2.0 text with a copyright header), .gitignore and .gitattributes.

### Step 18: Showcase artifact
The static export was published as a multi-file artifact, **"Showcase · Ngiti Dental Studio Website"**: `index.html` as the page (without its own doctype wrapper) and the other 10 pages, CSS, JS, fonts and images as files, so every page is clickable, including "About this project".

### Step 19: The one-click GitHub publisher
`publish-to-github.bat` and `publish-to-github.ps1` are based on the Presyo Menu Board template (fetched from GitHub; it had no "LAST STEP" block, so that part was written from the spec), with this project's settings, `.github/*` excluded from the release zip, and the Social preview helper added. Both files are ASCII with CRLF line endings. Tested with PowerShell 7.4.6, a stand-in `gh` script and a local bare repository:

| Check | Result |
|---|---|
| Run 1: git init, repo-local name/email from `gh api user`, commit "Ngiti Dental Theme v1.0.0" | OK |
| Run 1: repo created, `main` pushed, 8 topics, `claude-improve` label, tag `v1.0.0` pushed, release created, Pages call | OK, exit code 0 |
| Release zip | 85 files, all under `ngiti-dental-theme/`; no `.git`, `.github` or `publish-to-github` entries |
| LAST STEP block | Found `.github/social-preview.jpg` |
| Run 2 | "No new changes", "Repository already exists", "Release v1.0.0 already exists", exit code 0 |

The project is published by Efraim by double-clicking `publish-to-github.bat`.

### Step 20: Blogger post
`2026-10-23-blogger-ngiti-dental-theme.html` follows the Bukas Hours reference template: the `.devs-*` CSS and the slider/tab/copy script copied verbatim, a working phone-size demo (menu, live open-now badge, day chips that skip Sundays, PH mobile validation, reference number), real measured numbers, real code excerpts, three HTML recreations of the UI, a blank screenshot slot, and six FAQs. The visible FAQ and the FAQPage schema are generated from one Python list; a check confirmed the questions and answers match word for word and the `@graph` holds TechArticle + FAQPage. The Labels line is 190 of 200 characters (one secondary keyword dropped to fit). Rendering checks at 900 px and 390 px in light and dark: no sideways scroll, no page errors, demo flow and FAQ work. One bug was fixed: the success panel showed before submitting because the Blogger page has no global `[hidden]` rule.

### Step 21: Featured image
A 1200×630 HTML scene (Bricolage Grotesque, JetBrains Mono, IBM Plex Sans loaded from npm @fontsource files) with real screenshots of the theme inside a laptop and a phone, rendered by Playwright at 2× (2400×1260) and saved as JPG quality 88. The first render had the phone showing the wrong part of the page and overlapping "efraimray.com"; both were fixed. The image is also `.github/social-preview.jpg`.

### Step 22: Packaging
The repository folder was zipped as `ngiti-dental-theme.zip` with the folder at the top level (installs through wp-admin → Upload Theme), keeping `.gitignore`, `.gitattributes`, `.github/` and the publisher files.

## 4. What You Received

| Deliverable | Where |
|---|---|
| Theme + repository (zip) | `ngiti-dental-theme.zip` (extract to `C:\Users\YodnoX\Documents\_Codes`) |
| Showcase artifact | "Showcase · Ngiti Dental Studio Website" in your Claude artifacts |
| Static demo | `demo/` → https://efraimray.github.io/ngiti-dental-theme/demo/ after publishing |
| Blogger post | `2026-10-23-blogger-ngiti-dental-theme.html` |
| Featured image | `ngiti-dental-theme-featured.jpg` and `.github/social-preview.jpg` |
| Docs | `README.md`, `docs/BUILD-LOG.md`, `docs/TUTORIAL.md`, `docs/DEPLOYMENT.md`, `readme.txt`, `CHANGELOG.md` |
| Tests and tools | `tests/site.test.js`, `tools/export-static.py`, `tools/arch_svg.py` |
| GitHub publisher | `publish-to-github.bat` + `publish-to-github.ps1` |

## 5. Known Limits and What's Next

- The form sends a **request**, not a confirmed booking; there is no live slot calendar yet.
- Holidays are not in the schedule; the badge treats a holiday weekday as open.
- Emails depend on the host's mail setup; use an SMTP plugin on a real site.
- Post types live in the theme for an easy hand-off; WordPress.org's theme directory would require moving them to a plugin.
- Tested on WordPress 6.5.5 with SQLite and PHP 8.3, not yet on a live shared host with MySQL or a newer WordPress.
- The Privacy Notice and Disclaimer are templates and need review for a real clinic.
- Roadmap: real-time slot picker, Tagalog and Cebuano translations, holiday closures, block patterns.

Want a change? Open an issue on the repository with the label **`claude-improve`** and the nightly run will implement it in a new version.
