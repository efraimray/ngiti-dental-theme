#!/usr/bin/env python3
"""Export the running WordPress site (with this theme active) to static HTML in demo/.

The static copy is what GitHub Pages and the showcase artifact show. Server features
are simulated in the browser: theme.js runs with ngitiTheme.demo = true, so forms
validate and show a success state with a reference number but send nothing.

Usage:
    python3 tools/export-static.py http://127.0.0.1:8088 demo
Needs only the Python standard library.
"""
import os
import re
import shutil
import sys
import urllib.request
from datetime import date

PAGES = [
    ("", "index.html"),
    ("about/", "about.html"),
    ("services/", "services.html"),
    ("team/", "team.html"),
    ("appointment/", "appointment.html"),
    ("faq/", "faq.html"),
    ("contact/", "contact.html"),
    ("privacy-notice/", "privacy-notice.html"),
    ("disclaimer/", "disclaimer.html"),
    ("about-this-project/", "about-this-project.html"),
    ("this-page-does-not-exist/", "404.html"),
]
PUBLIC = "https://efraimray.github.io/ngiti-dental-theme/demo/"
THEME_PATH = "wp-content/themes/ngiti-dental-theme/"


def fetch(url):
    try:
        with urllib.request.urlopen(url) as r:
            return r.read().decode("utf-8")
    except urllib.error.HTTPError as e:  # the 404 page still has a body
        return e.read().decode("utf-8")


def convert(html, base):
    b = re.escape(base.rstrip("/"))
    slug_map = {p: f for p, f in PAGES}

    # 1. Drop WordPress core extras the static copy does not need.
    html = re.sub(r"<link[^>]+(wp-includes|api\.w\.org|EditURI|wlwmanifest|shortlink|oembed|wp-json|/feed/)[^>]*>\s*", "", html)
    html = re.sub(r"<meta name=\"generator\"[^>]*>\s*", "", html)
    html = re.sub(r"<script[^>]*>[^<]*_wpemojiSettings.*?</script>\s*", "", html, flags=re.S)
    html = re.sub(r"<style[^>]*id=\"wp-emoji-styles-inline-css\"[^>]*>.*?</style>\s*", "", html, flags=re.S)
    html = re.sub(r"<script[^>]*wp-emoji[^>]*>\s*</script>\s*", "", html)

    # 2. Absolute URLs for canonical / Open Graph, relative links everywhere else.
    def absolute(m):
        path = m.group(2)
        if path.startswith(THEME_PATH):
            path = path[len(THEME_PATH):]
        else:
            path = slug_map.get(path, path)
            if path == "index.html":
                path = ""
        return m.group(1) + PUBLIC + path + '"'

    html = re.sub(r'(<link rel="canonical" href=")' + b + r'/([^"]*)"', absolute, html)
    html = re.sub(r'(<meta property="og:(?:url|image)" content=")' + b + r'/([^"]*)"', absolute, html)

    html = re.sub(b + "/" + re.escape(THEME_PATH), "", html)

    def relative(m):
        path = m.group(1)
        query = ""
        if "?" in path:
            path, query = path.split("?", 1)
        if path in slug_map:
            return slug_map[path]
        return (path or "index.html") + ("?" + query if query else "")

    html = re.sub(b + r"/([a-z0-9\-/]*/?(?:\?[^\"'#]*)?)", relative, html)
    html = html.replace(base.rstrip("/"), "index.html")

    # 3. Forms: simulated in the browser.
    html = re.sub(r'action="[^"]*admin-post\.php"', 'action="#"', html)
    html = html.replace('"demo":""', '"demo":true')
    # Drop the ?ver= cache busters on local assets.
    html = re.sub(r'((?:assets/[a-z/\-\.]+\.(?:css|js)))\?ver=[0-9.]+', r"\1", html)
    return html


def main():
    base = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8088"
    out = sys.argv[2] if len(sys.argv) > 2 else "demo"
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    os.makedirs(out, exist_ok=True)
    for path, name in PAGES:
        html = convert(fetch(base.rstrip("/") + "/" + path), base)
        with open(os.path.join(out, name), "w", encoding="utf-8") as f:
            f.write(html)
        print("wrote", name, len(html), "bytes")

    # Theme assets used by the pages.
    if os.path.exists(os.path.join(out, "assets")):
        shutil.rmtree(os.path.join(out, "assets"))
    shutil.copytree(os.path.join(root, "assets"), os.path.join(out, "assets"))
    shutil.copy(os.path.join(root, "screenshot.png"), os.path.join(out, "screenshot.png"))

    # sitemap.xml and robots.txt for the static copy.
    today = date.today().isoformat()
    urls = "".join(
        "  <url><loc>%s%s</loc><lastmod>%s</lastmod></url>\n" % (PUBLIC, "" if n == "index.html" else n, today)
        for _, n in PAGES if n != "404.html"
    )
    with open(os.path.join(out, "sitemap.xml"), "w", encoding="utf-8") as f:
        f.write('<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + urls + "</urlset>\n")
    with open(os.path.join(out, "robots.txt"), "w", encoding="utf-8") as f:
        f.write("User-agent: *\nAllow: /\n\nSitemap: %ssitemap.xml\n" % PUBLIC)
    print("wrote assets/, sitemap.xml, robots.txt")


if __name__ == "__main__":
    main()
