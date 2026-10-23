/**
 * End-to-end checks for the static demo (and optionally the live WordPress site).
 *
 *   python3 -m http.server 8090 --directory demo &
 *   node tests/site.test.js http://127.0.0.1:8090/            # static demo
 *   node tests/site.test.js http://127.0.0.1:8088/ --wp       # WordPress with the theme active
 *
 * Needs Playwright (npm i -D playwright). Prints a results table and exits 1 on failure.
 */
const { chromium } = require('playwright');

const base = process.argv[2] || 'http://127.0.0.1:8090/';
const wp = process.argv.includes('--wp');
const pages = wp
  ? ['', 'about/', 'services/', 'team/', 'appointment/', 'faq/', 'contact/', 'privacy-notice/', 'disclaimer/', 'about-this-project/']
  : ['index.html', 'about.html', 'services.html', 'team.html', 'appointment.html', 'faq.html', 'contact.html', 'privacy-notice.html', 'disclaimer.html', 'about-this-project.html', '404.html'];
const results = [];
function record(name, ok, detail) { results.push({ name, ok, detail: detail || '' }); }

function nextOpenDay() {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  while (d.getDay() === 0) d.setDate(d.getDate() + 1); // skip Sunday (closed)
  return d.toISOString().slice(0, 10);
}
function nextSunday() {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  while (d.getDay() !== 0) d.setDate(d.getDate() + 1);
  return d.toISOString().slice(0, 10);
}

(async () => {
  const browser = await chromium.launch();

  // 1. Every page at 1280 and 390, light and dark: no sideways scroll, no JS errors, links resolve.
  const seen = new Set();
  for (const scheme of ['light', 'dark']) {
    for (const width of [1280, 390]) {
      const ctx = await browser.newContext({ viewport: { width, height: 900 }, colorScheme: scheme });
      for (const path of pages) {
        const page = await ctx.newPage();
        const errors = [];
        page.on('pageerror', (e) => errors.push(e.message));
        // 4xx/5xx are reported with their URL by the response handler below, so skip the generic console line.
        page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push(m.text()); });
        // wp-includes/*.css: core files missing from the slimmed WordPress build used for testing.
        page.on('requestfailed', (r) => { if (!/wp-includes/.test(r.url())) errors.push('request failed: ' + r.url()); });
        page.on('response', (r) => { if (r.status() >= 400 && !/wp-includes|this-page-does-not-exist|404\.html/.test(r.url())) errors.push(r.status() + ' ' + r.url()); });
        await page.goto(base + path, { waitUntil: 'networkidle' });
        const [sw, vw] = await page.evaluate(() => [document.documentElement.scrollWidth, window.innerWidth]);
        record(`${path || 'home'} ${width}px ${scheme}`, sw <= vw && errors.length === 0, sw > vw ? `scrollWidth ${sw} > ${vw}` : errors.join('; '));
        if (width === 1280 && scheme === 'light') {
          const links = await page.$$eval('a[href]', (as) => as.map((a) => a.href));
          for (const href of links) {
            if (!href.startsWith(base) || seen.has(href.split('#')[0])) continue;
            seen.add(href.split('#')[0]);
          }
        }
        await page.close();
      }
      await ctx.close();
    }
  }
  const ctx0 = await browser.newContext();
  const req = ctx0.request;
  let broken = [];
  for (const href of seen) {
    const r = await req.get(href);
    if (r.status() !== 200) broken.push(r.status() + ' ' + href);
  }
  record(`internal links resolve (${seen.size} unique)`, broken.length === 0, broken.join('; '));

  // 2. Mobile menu opens and closes.
  const m = await browser.newPage({ viewport: { width: 390, height: 800 } });
  await m.goto(base + pages[0], { waitUntil: 'networkidle' });
  await m.click('[data-ngiti-menu-toggle]');
  const open = await m.$eval('#primary-nav', (n) => n.classList.contains('is-open') && getComputedStyle(n).visibility === 'visible');
  await m.keyboard.press('Escape');
  const closed = await m.$eval('#primary-nav', (n) => !n.classList.contains('is-open'));
  record('mobile menu opens and closes with Escape', open && closed);

  // 3. Theme toggle flips data-theme.
  await m.click('[data-ngiti-theme-toggle]');
  const t1 = await m.evaluate(() => document.documentElement.getAttribute('data-theme'));
  await m.click('[data-ngiti-theme-toggle]');
  const t2 = await m.evaluate(() => document.documentElement.getAttribute('data-theme'));
  record('theme toggle switches light/dark', t1 !== t2 && !!t1 && !!t2, `${t1} -> ${t2}`);

  // 4. Pure logic: open status, validators, tooth names.
  const unit = await m.evaluate(() => {
    const T = window.ngitiTest, wk = { 1: [['09:00', '12:00'], ['13:00', '17:00']], 2: [['09:00', '12:00'], ['13:00', '17:00']], 3: [['09:00', '12:00'], ['13:00', '17:00']], 4: [['09:00', '12:00'], ['13:00', '17:00']], 5: [['09:00', '12:00'], ['13:00', '17:00']], 6: [['09:00', '13:00']], 7: [] };
    const out = [];
    const s = (day, h, mm) => T.computeStatus(wk, { day, min: h * 60 + mm });
    out.push(['Mon 10:00 open', s(1, 10, 0).state === 'open']);
    out.push(['Mon 11:45 closing soon', s(1, 11, 45).state === 'soon']);
    out.push(['Mon 12:20 lunch closed, opens 1:00 PM', s(1, 12, 20).state === 'closed' && T.timeLabel(s(1, 12, 20).at) === '1:00 PM']);
    out.push(['Sat 13:30 closed, next Mon 9:00 AM (offset 2)', s(6, 13, 30).offset === 2 && s(6, 13, 30).day === 1]);
    out.push(['Sun closed, opens tomorrow', s(7, 10, 0).offset === 1]);
    out.push(['12:00 shows as 12:00 NN', T.timeLabel(720) === '12:00 NN']);
    out.push(['mobile 0917 555 0142 valid', T.validators.mobile('0917 555 0142')]);
    out.push(['mobile +63 917-555-0142 valid', T.validators.mobile('+63 917-555-0142')]);
    out.push(['mobile 02 8123 4567 rejected', !T.validators.mobile('02 8123 4567')]);
    out.push(['email empty allowed', T.validators.email('')]);
    out.push(['email "ana@" rejected', !T.validators.email('ana@')]);
    out.push(['date today rejected', T.validators.date(new Date().toISOString().slice(0, 10), [7]) !== '']);
    out.push(['tooth 16 = upper right first molar', T.describeTooth(16, []).name === 'Upper right first molar']);
    out.push(['tooth 38 = wisdom tooth', T.describeTooth(38, []).type === 'wisdom']);
    return out;
  });
  unit.forEach(([n, ok]) => record('unit: ' + n, ok));
  await m.close();

  // 5. Appointment flow: empty submit shows errors; Sunday rejected; valid request reaches success.
  const a = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await a.goto(base + (wp ? 'appointment/' : 'appointment.html'), { waitUntil: 'networkidle' });
  await a.click('form[data-ngiti-form="appointment"] button[type=submit]');
  const errCount = await a.$$eval('form[data-ngiti-form="appointment"] .field.has-error', (e) => e.length);
  record('appointment: empty submit flags required fields', errCount >= 5, `${errCount} fields flagged`);
  await a.fill('#ngiti-appointment-form-name', 'Ana Dela Cruz');
  await a.fill('#ngiti-appointment-form-mobile', '0917 555 0199');
  await a.selectOption('#ngiti-appointment-form-service', { index: 1 });
  await a.fill('#ngiti-appointment-form-date', nextSunday());
  await a.check('#ngiti-appointment-form-consent');
  await a.click('form[data-ngiti-form="appointment"] button[type=submit]');
  const sundayMsg = await a.textContent('#ngiti-appointment-form-date-error');
  record('appointment: Sunday is rejected', /closed/i.test(sundayMsg || ''), sundayMsg);
  await a.fill('#ngiti-appointment-form-date', nextOpenDay());
  await a.check('[name="ngiti_time"][value="afternoon"]');
  await a.fill('#ngiti-appointment-form-message', 'First check-up, a bit nervous.');
  await Promise.all([
    wp ? a.waitForNavigation({ waitUntil: 'networkidle' }) : Promise.resolve(),
    a.click('form[data-ngiti-form="appointment"] button[type=submit]'),
  ]);
  await a.waitForSelector('.form-success .ref', { timeout: 5000 }).catch(() => {});
  const ref = await a.$eval('.form-success .ref', (e) => e.textContent).catch(() => '');
  record('appointment: valid request shows success + reference', /^NGT-\d{6}-[A-Z0-9]{4}$/.test(ref.trim()), ref || a.url());
  await a.close();

  // 6. Contact form flow.
  const c = await browser.newPage({ viewport: { width: 390, height: 900 } });
  await c.goto(base + (wp ? 'contact/' : 'contact.html'), { waitUntil: 'networkidle' });
  if (wp) await c.waitForTimeout(61000); // the theme allows one request per minute per IP
  await c.fill('#ngiti-message-form-name', 'Ben Santos');
  await c.fill('#ngiti-message-form-mobile', '09175550123');
  await c.fill('#ngiti-message-form-email', 'ben@example.com');
  await c.fill('#ngiti-message-form-message', 'Do you accept HMO cards?');
  await c.check('#ngiti-message-form-consent');
  await Promise.all([
    wp ? c.waitForNavigation({ waitUntil: 'networkidle' }) : Promise.resolve(),
    c.click('form[data-ngiti-form="message"] button[type=submit]'),
  ]);
  await c.waitForSelector('.form-success .ref', { timeout: 5000 }).catch(() => {});
  const ref2 = await c.$eval('.form-success .ref', (e) => e.textContent).catch(() => '');
  record('contact: message form shows success at 390px', /^NGT-/.test(ref2.trim()), ref2);
  await c.close();

  // 7. Tooth finder.
  const s = await browser.newPage({ viewport: { width: 390, height: 900 } });
  await s.goto(base + (wp ? 'services/' : 'services.html'), { waitUntil: 'networkidle' });
  await s.click('.tooth[data-fdi="46"]');
  const h = await s.textContent('[data-ngiti-tooth-result] h3');
  const items = await s.$$eval('[data-ngiti-tooth-result] li', (l) => l.length);
  record('tooth finder: 46 -> lower right first molar + services', /Lower right first molar/.test(h) && items >= 3, `${h} (${items} services)`);
  await s.close();

  await browser.close();

  const failed = results.filter((r) => !r.ok);
  console.log('| Check | Result | Detail |\n|---|---|---|');
  results.forEach((r) => console.log(`| ${r.name} | ${r.ok ? 'PASS' : 'FAIL'} | ${r.detail.replace(/\|/g, '/')} |`));
  console.log(`\n${results.length - failed.length}/${results.length} passed`);
  process.exit(failed.length ? 1 : 0);
})();
