"""Snapshot the current public PHP site into eca-pages for Netlify."""
from __future__ import annotations

import re
import shutil
import urllib.request
from pathlib import Path

ROOT = Path(r"D:\Website")
SRC = ROOT / "v1"
DEST = ROOT / "eca-pages"
BASE = "http://127.0.0.1:8765"

LISTING_PAGES = [
    ("/education.php", "education.html"),
    ("/education-training.php", "education-training.html"),
    ("/education-knowledge.php", "education-knowledge.html"),
    ("/education-learner.php", "education-learner.html"),
    ("/education-development.php", "education-development.html"),
    ("/education-policy.php", "education-policy.html"),
    ("/education-resources.php", "education-resources.html"),
]


def fetch(path: str) -> str:
    with urllib.request.urlopen(BASE + path, timeout=30) as response:
        return response.read().decode("utf-8", errors="replace")


def rewrite(html: str) -> str:
    html = html.replace('<base href="/">', "")
    html = re.sub(
        r'href="/education-course\.php\?slug=([^"&]+)"',
        r'href="/education-course-\1.html"',
        html,
    )
    html = re.sub(
        r'href="/education-article\.php\?kind=[^"&]+&amp;slug=([^"&]+)"',
        r'href="/education-article-\1.html"',
        html,
    )
    html = re.sub(
        r'href="/education-article\.php\?kind=[^"&]+&slug=([^"&]+)"',
        r'href="/education-article-\1.html"',
        html,
    )
    html = re.sub(
        r'href="/education-programme\.php\?slug=([^"&]+)"',
        r'href="/education-programme-\1.html"',
        html,
    )
    html = re.sub(r'href="/education-knowledge\.php\?[^"]+"', 'href="/education-knowledge.html"', html)
    html = re.sub(r'href="/education-policy\.php\?[^"]+"', 'href="/education-policy.html"', html)
    html = re.sub(r'href="/education-training\.php\?[^"]+"', 'href="/education-training.html"', html)
    html = re.sub(r'href="/education-resources\.php\?[^"]+"', 'href="/education-resources.html"', html)
    replacements = [
        ("/education.php", "/education.html"),
        ("/education-training.php", "/education-training.html"),
        ("/education-knowledge.php", "/education-knowledge.html"),
        ("/education-learner.php", "/education-learner.html"),
        ("/education-development.php", "/education-development.html"),
        ("/education-policy.php", "/education-policy.html"),
        ("/education-resources.php", "/education-resources.html"),
        ('href="/education-download.php', 'href="https://eca.co.sz/education-download.php'),
        ('href="/cpd/', 'href="https://eca.co.sz/cpd/'),
        ('href="/download.php?', 'href="https://eca.co.sz/download.php?'),
        ('href="/application.php"', 'href="/application.html"'),
        ('href="application.php"', 'href="application.html"'),
        ('href="/renewal.php"', 'href="/renewal.html"'),
        ('href="renewal.php"', 'href="renewal.html"'),
        ('href="/directory.php"', 'href="/directory.html"'),
        ('href="directory.php"', 'href="directory.html"'),
        ('href="/about.php"', 'href="/about.html"'),
        ('href="about.php"', 'href="about.html"'),
        ('href="/about-bod.php"', 'href="/about-bod.html"'),
        ('href="about-bod.php"', 'href="about-bod.html"'),
        ('href="/contact.php"', 'href="/contact.html"'),
        ('href="contact.php"', 'href="contact.html"'),
        ('href="/faq.php"', 'href="/faq.html"'),
        ('href="faq.php"', 'href="faq.html"'),
        ('href="/news.php"', 'href="/news.html"'),
        ('href="news.php"', 'href="news.html"'),
        ('href="/gallery.php"', 'href="/gallery.html"'),
        ('href="gallery.php"', 'href="gallery.html"'),
        ('href="/index.php"', 'href="/index.html"'),
        ('href="index.php"', 'href="index.html"'),
        ('href="/checklist.php"', 'href="/checklist.html"'),
        ('href="checklist.php"', 'href="checklist.html"'),
        ('href="balingani-directory.php"', 'href="balingani-directory.html"'),
        ('href="/resources.php"', 'href="/education-resources.html"'),
    ]
    for old, new in replacements:
        html = html.replace(old, new)
    return html


def update_existing_html() -> int:
    learning = """                            <p class="eca-mega-label">Learning</p>
                            <a href="education.html">Education hub</a>
                            <a href="education-training.html">Training &amp; CPD</a>
                            <a href="education-knowledge.html">Knowledge centre</a>
                            <a href="education-learner.html">Learner Portal</a>
                            <a href="education-development.html">Contractor development</a>
                            <a href="education-policy.html">Industry &amp; policy</a>
                            <a href="education-resources.html">Resources</a>"""
    footer_edu = """                <h5>Education</h5>
                <a href="education.html">Education hub</a>
                <a href="education-training.html">Training &amp; CPD</a>
                <a href="education-knowledge.html">Knowledge centre</a>
                <a href="education-resources.html">Resources</a>"""
    home_card = """            <a class="eca-action-card" href="education.html">
                <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                <h3>Education &amp; CPD</h3>
                <p>Training, contractor knowledge, development programmes and policy briefings in one place.</p>
                <span>Explore education</span>
            </a>"""
    changed = 0
    for path in DEST.rglob("*.html"):
        if "assets" in path.parts or "lib" in path.parts:
            continue
        text = path.read_text(encoding="utf-8")
        original = text
        text = text.replace(">Apply</a>", ">Membership application</a>")
        text = text.replace(">Renew</a>", ">Membership renewal</a>")
        text = re.sub(
            r'<p class="eca-mega-label">Learning</p>\s*<a href="[^"]*training\.php">Training</a>\s*<a href="resources\.html">Resources</a>',
            learning,
            text,
        )
        text = re.sub(
            r'<h5>Education</h5>\s*<a href="resources\.html">Resources</a>',
            footer_edu,
            text,
        )
        text = re.sub(
            r'<a class="eca-action-card" href="https://eca\.co\.sz/training\.php">\s*'
            r'<i class="fas fa-graduation-cap"[^>]*></i>\s*'
            r'<h3>CPD and training</h3>\s*'
            r'<p>Open ECA resources and continuing professional development for contractors\.</p>\s*'
            r'<span>Explore training</span>\s*</a>',
            home_card,
            text,
        )
        text = text.replace('href="/training.php"', 'href="/education-training.html"')
        text = text.replace('href="https://eca.co.sz/training.php"', 'href="education.html"')
        if text != original:
            path.write_text(text, encoding="utf-8")
            changed += 1
    return changed


def collect_detail_pages(listing_html: dict[str, str]) -> list[tuple[str, str]]:
    pages: list[tuple[str, str]] = []
    courses = set(re.findall(r'education-course\.php\?slug=([^"&]+)', listing_html.get("education-training.html", "")))
    programmes = set(re.findall(r'education-programme\.php\?slug=([^"&]+)', listing_html.get("education-development.html", "")))
    knowledge = set(re.findall(r'education-article\.php\?kind=knowledge(?:&amp;|&)slug=([^"&]+)', listing_html.get("education-knowledge.html", "")))
    policy = set(re.findall(r'education-article\.php\?kind=policy(?:&amp;|&)slug=([^"&]+)', listing_html.get("education-policy.html", "")))
    for slug in sorted(courses):
        pages.append((f"/education-course.php?slug={slug}", f"education-course-{slug}.html"))
    for slug in sorted(programmes):
        pages.append((f"/education-programme.php?slug={slug}", f"education-programme-{slug}.html"))
    for slug in sorted(knowledge):
        pages.append((f"/education-article.php?kind=knowledge&slug={slug}", f"education-article-{slug}.html"))
    for slug in sorted(policy):
        pages.append((f"/education-article.php?kind=policy&slug={slug}", f"education-article-{slug}.html"))
    return pages


def main() -> None:
    dest_css = DEST / "css"
    dest_css.mkdir(parents=True, exist_ok=True)
    shutil.copy2(SRC / "css" / "education.css", dest_css / "education.css")
    print("copied education.css")

    listing_html: dict[str, str] = {}
    for url, filename in LISTING_PAGES:
        raw = fetch(url)
        listing_html[filename] = raw
        (DEST / filename).write_text(rewrite(raw), encoding="utf-8")
        print("wrote", filename)

    for url, filename in collect_detail_pages(listing_html):
        (DEST / filename).write_text(rewrite(fetch(url)), encoding="utf-8")
        print("wrote", filename)

    print("updated existing html", update_existing_html())


if __name__ == "__main__":
    main()
