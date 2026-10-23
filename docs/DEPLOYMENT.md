# Deploying Ngiti Dental

## Easiest: double-click (Windows)

1. One-time setup (PowerShell):
   ```powershell
   winget install Git.Git
   winget install GitHub.cli
   ```
   Close and reopen PowerShell afterwards.
2. Extract `ngiti-dental-theme.zip` to `C:\Users\YodnoX\Documents\_Codes`.
3. Open `_Codes\ngiti-dental-theme` and double-click **publish-to-github.bat**.

The script runs six steps: checks Git and GitHub CLI (and signs you in through the browser the first time), commits the files, creates the public repository `EfraimRay/ngiti-dental-theme` and pushes `main`, adds the topics and the `claude-improve` label, tags `v1.0.0` and publishes a release with an installable `ngiti-dental-theme.zip`, then turns on GitHub Pages.

When it prints **ALL DONE**, the repository's Settings page opens with File Explorer showing `.github\social-preview.jpg`, and the path is already copied. Scroll to **Social preview → Edit → Upload an image…**, press **Ctrl+V** in File name and Enter. GitHub has no API for this one step.

Running it again later is safe: it pushes new commits and creates a release only for a version that does not exist yet.

## With GitHub Desktop

1. File → Add local repository → choose the folder → "create a repository" if asked.
2. Commit to `main` with the message `Ngiti Dental Theme v1.0.0`.
3. Publish repository → untick "Keep this code private".
4. On github.com: Releases → Draft a new release → tag `v1.0.0` → attach a zip of the folder.

## With the git CLI

```bash
cd ngiti-dental-theme
git init -b main
git add -A
git commit -m "Ngiti Dental Theme v1.0.0"
gh repo create EfraimRay/ngiti-dental-theme --public --source . --push \
  --description "Free classic WordPress theme for dental clinics"
git tag v1.0.0 && git push origin v1.0.0
gh release create v1.0.0 ../ngiti-dental-theme.zip --notes-file CHANGELOG.md
```

## GitHub Pages (live demo)

Settings → Pages → Source: **Deploy from a branch** → `main` / `(root)`. The demo appears at https://efraimray.github.io/ngiti-dental-theme/demo/ within a minute or two. The publisher does this for you.

## Releasing a new version

1. Change the version everywhere: `style.css` (Version), `functions.php` (`NGITI_VERSION`), `readme.txt` (Stable tag + changelog), `CHANGELOG.md`, `languages/ngiti-dental-theme.pot` (Project-Id-Version) and **`$Version` in `publish-to-github.ps1`**.
2. Double-click `publish-to-github.bat`, or tag manually:
   ```bash
   git commit -am "Ngiti Dental Theme v1.1.0"
   git tag v1.1.0 && git push origin main v1.1.0
   ```

## Installing on a client's WordPress site

- **wp-admin**: Appearance → Themes → Add New → Upload Theme → choose the release zip → Activate → Create sample content.
- **Shared hosting / cPanel**: File Manager → `public_html/wp-content/themes/` → Upload the zip → Extract → activate in wp-admin.
- **WP-CLI**: `wp theme install ngiti-dental-theme.zip --activate`

## Custom domain

1. Point the domain's A record to the host's IP (or use the host's nameservers).
2. Settings → General: set both WordPress Address and Site Address to `https://clinicdomain.ph`.
3. Turn on the host's free SSL (AutoSSL / Let's Encrypt).
4. For the static demo on GitHub Pages instead: Settings → Pages → Custom domain, then add a CNAME record to `efraimray.github.io`.

## Connecting the forms

The WordPress theme already saves every request and sends an email with `wp_mail()`. To make that email arrive reliably:

1. **SMTP (recommended)**: install an SMTP plugin and connect the clinic's mailbox or a transactional email service. Send a test from the plugin.
2. **Set the recipient**: Customize → Clinic details → "Send appointment requests to".
3. **Google Sheets**: create a Google Apps Script web app that appends rows, then add to a small plugin:
   ```php
   add_action( 'ngiti_request_received', function ( $ref, $lines ) {
       wp_remote_post( 'https://script.google.com/macros/s/YOUR_ID/exec', array(
           'body' => array( 'ref' => $ref, 'details' => implode( "\n", $lines ) ),
       ) );
   }, 10, 2 );
   ```
4. **Formspree-style service** (static demo only): change the form `action` in `demo/*.html` to the service's endpoint and set `"demo":false` in the inline `ngitiTheme` script.

## Regenerating the static demo

With the theme active on a local site (LocalWP, wp-env or `php -S`):

```bash
python3 tools/export-static.py http://localhost:8888 demo
python3 -m http.server 8090 --directory demo &
node tests/site.test.js http://127.0.0.1:8090/
```
