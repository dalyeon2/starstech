#!/usr/bin/env python3
import sys
from pathlib import Path
from urllib.parse import urlsplit, urlunsplit, parse_qsl, urlencode
import re


ROOT = Path(__file__).resolve().parent.parent
ASSET_EXTS = (".css", ".js")
ASSET_PATTERN = re.compile(
    r'(?P<attr>\b(?:href|src)\s*=\s*)(?P<quote>["\'])(?P<url>[^"\']+)(?P=quote)',
    re.IGNORECASE,
)


def find_html_roots() -> list[Path]:
    roots = []
    for entry in ROOT.iterdir():
        if entry.is_dir() and (entry / "index.html").exists():
            roots.append(entry)
    return roots


def update_url(url: str, html_path: Path) -> tuple[str, str | None]:
    parts = urlsplit(url)
    if parts.scheme or parts.netloc:
        return url, None
    if not parts.path.lower().endswith(ASSET_EXTS):
        return url, None

    asset_path = (html_path.parent / parts.path).resolve()
    if not asset_path.exists():
        return url, f"missing asset: {asset_path}"

    version = str(int(asset_path.stat().st_mtime * 1000))
    params = [(k, v) for k, v in parse_qsl(parts.query, keep_blank_values=True) if k not in ("v", "ver")]
    params.append(("v", version))
    new_query = urlencode(params)
    updated = urlunsplit((parts.scheme, parts.netloc, parts.path, new_query, parts.fragment))
    return updated, None


def update_html_file(path: Path, warnings: list[str]) -> bool:
    content = path.read_text(encoding="utf-8")
    changed = False

    def replacer(match: re.Match) -> str:
        nonlocal changed
        url = match.group("url")
        updated, warn = update_url(url, path)
        if warn:
            warnings.append(f"{path}: {warn}")
        if updated != url:
            changed = True
        return f"{match.group('attr')}{match.group('quote')}{updated}{match.group('quote')}"

    updated_content = ASSET_PATTERN.sub(replacer, content)
    if changed and updated_content != content:
        path.write_text(updated_content, encoding="utf-8")
    return changed


def main() -> int:
    html_roots = find_html_roots()
    if not html_roots:
        print("No html roots found.")
        return 1

    warnings: list[str] = []
    updated_files = 0
    for root in html_roots:
        for html_path in root.rglob("*.html"):
            if update_html_file(html_path, warnings):
                updated_files += 1

    if warnings:
        print("Warnings:")
        for warn in warnings:
            print(f"- {warn}")

    print(f"Updated {updated_files} html files.")
    print("Run this before FTP deploy to refresh cache-busting tags.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
