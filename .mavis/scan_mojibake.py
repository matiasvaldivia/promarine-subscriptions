#!/usr/bin/env python3
"""Scan-Mojibake.py - Detecta mojibake REAL en archivos (basado en bytes)."""
import sys
from pathlib import Path

# Reusar el mismo mapeo
sys.path.insert(0, str(Path(__file__).parent))
from fix_mojibake import REPLACEMENTS


def scan(path: Path, exts=('.blade.php', '.js')):
    if path.is_file():
        files = [path]
    else:
        files = []
        for ext in exts:
            files.extend(path.rglob(f'*{ext}'))
    files = sorted(set(files))

    total = 0
    for f in files:
        try:
            data = f.read_bytes()
        except Exception as e:
            print(f"  ERROR: {f}: {e}", file=sys.stderr)
            continue
        hits = 0
        types = {}
        for search, _, desc in REPLACEMENTS:
            c = data.count(search)
            if c > 0:
                hits += c
                types[desc] = c
        if hits > 0:
            rel = f.relative_to(Path.cwd()) if f.is_relative_to(Path.cwd()) else f
            print(f"  [{hits:>4} hits] {rel}")
            for desc, c in sorted(types.items(), key=lambda x: -x[1]):
                print(f"           {c}x  {desc}")
            total += hits
    print()
    print(f"Total hits REALES: {total}")


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Uso: python scan_mojibake.py <path>")
        sys.exit(1)
    scan(Path(sys.argv[1]))
