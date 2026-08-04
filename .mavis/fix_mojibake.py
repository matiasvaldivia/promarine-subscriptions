#!/usr/bin/env python3
"""
Fix-Mojibake.py - Repara mojibake UTF-8 en archivos blade y JS.

Trabaja con BYTES puros para evitar problemas de encoding de PowerShell.

Dos tipos de mojibake:
  TIPO A: bytes UTF-8 de los chars Unicode mojibake (ej: 'Ã­' = C3 83 C2 AD)
  TIPO B: bytes UTF-8 que se ven como secuencia de 3 chars Latin-1 mojibake
          (ej: 'â€"' = C3 A2 E2 82 AC E2 80 9D, debe ser '—' = E2 80 94)

Uso:
    python fix_mojibake.py <path> [--dry-run] [--ext .blade.php .js]
"""

import os
import sys
import argparse
from pathlib import Path


# (buscar_bytes, reemplazar_bytes, descripcion)
# Las keys son bytes de mojibake; los values son los bytes correctos
REPLACEMENTS = [
    # === TIPO A: vocales con tilde (mojibake Latin-1 -> UTF-8) ===
    (b'\xc3\x83\xc2\xad', b'\xc3\xad', 'i con tilde'),
    (b'\xc3\x83\xc2\xa1', b'\xc3\xa1', 'a con tilde'),
    (b'\xc3\x83\xc2\xa9', b'\xc3\xa9', 'e con tilde'),
    (b'\xc3\x83\xc2\xb3', b'\xc3\xb3', 'o con tilde'),
    (b'\xc3\x83\xc2\xba', b'\xc3\xba', 'u con tilde'),
    (b'\xc3\x83\xc2\xb1', b'\xc3\xb1', 'enie'),
    (b'\xc3\x83\xc2\xbc', b'\xc3\xbc', 'u con dieresis'),
    (b'\xc3\x83\xc2\xa0', b'\xc3\xa0', 'a grave'),
    (b'\xc3\x83\xc2\xa8', b'\xc3\xa8', 'e grave'),
    (b'\xc3\x83\xc2\xac', b'\xc3\xac', 'i grave'),
    (b'\xc3\x83\xc2\xb2', b'\xc3\xb2', 'o grave'),
    (b'\xc3\x83\xc2\xb9', b'\xc3\xb9', 'u grave'),
    (b'\xc3\x83\xc2\xa4', b'\xc3\xa4', 'a con dieresis'),
    (b'\xc3\x83\xc2\xb6', b'\xc3\xb6', 'o con dieresis'),
    (b'\xc3\x83\xc2\xab', b'\xc3\xab', 'e con dieresis'),
    (b'\xc3\x83\xc2\xaf', b'\xc3\xaf', 'i con dieresis'),
    (b'\xc3\x83\xc2\xa7', b'\xc3\xa7', 'c cedilla'),
    # Mayusculas
    (b'\xc3\x83\xc2\x81', b'\xc3\x81', 'A con tilde'),
    (b'\xc3\x83\xc2\x89', b'\xc3\x89', 'E con tilde'),
    (b'\xc3\x83\xc2\x8d', b'\xc3\x8d', 'I con tilde'),
    (b'\xc3\x83\xc2\x93', b'\xc3\x93', 'O con tilde'),
    (b'\xc3\x83\xc2\x9a', b'\xc3\x9a', 'U con tilde'),
    (b'\xc3\x83\xc2\x91', b'\xc3\x91', 'N con tilde'),
    (b'\xc3\x83\xc2\x9c', b'\xc3\x9c', 'U con dieresis'),

    # === TIPO B: signos tipograficos (3 chars Latin-1 -> 1 char UTF-8) ===
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\x9d', b'\xe2\x80\x94', 'em dash'),
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\x9c', b'\xe2\x80\x93', 'en dash'),
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\xa6', b'\xe2\x80\xa6', 'ellipsis'),
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\x99', b'\xe2\x80\x99', 'right single quote'),
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\x98', b'\xe2\x80\x98', 'left single quote'),
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\x9c', b'\xe2\x80\x9c', 'left double quote'),
    (b'\xc3\xa2\xe2\x82\xac\xe2\x80\x9d', b'\xe2\x80\x9d', 'right double quote'),
    (b'\xc3\xa2\xe2\x82\xac', b'\xe2\x82\xac', 'euro'),

    # === Flechas ===
    (b'\xc3\xa2\xe2\x86\x90', b'\xe2\x86\x90', 'right arrow'),
    (b'\xc3\xa2\xe2\x86\x91', b'\xe2\x86\x91', 'up arrow'),
    (b'\xc3\xa2\xe2\x86\x92', b'\xe2\x86\x92', 'left arrow'),
    (b'\xc3\xa2\xe2\x86\x93', b'\xe2\x86\x93', 'down arrow'),

    # === Checkmarks y simbolos ===
    (b'\xc3\xa2\xe2\x9c\x93', b'\xe2\x9c\x93', 'check'),
    (b'\xc3\xa2\xe2\x9c\x97', b'\xe2\x9c\x97', 'cross'),
    (b'\xc3\xa2\xe2\x96\xa1', b'\xe2\x96\xa1', 'white square'),
    (b'\xc3\xa2\xe2\x96\xa3', b'\xe2\x96\xa3', 'white square with corners'),
    (b'\xc3\xa2\xe2\x97\x86', b'\xe2\x97\x86', 'diamond'),

    # === Latin-1 residual (Â = U+00C2) ===
    (b'\xc3\x82\xc2\xa0', b' ', 'NBSP -> space'),
    (b'\xc3\x82\xc2\xa1', b'\xc2\xa1', 'inverted exclamation'),
    (b'\xc3\x82\xc2\xbf', b'\xc2\xbf', 'inverted question'),
    (b'\xc3\x82\xc2\xa9', b'\xc2\xa9', 'copyright'),
    (b'\xc3\x82\xc2\xae', b'\xc2\xae', 'registered'),
    (b'\xc3\x82\xc2\xb0', b'\xc2\xb0', 'degree'),
    (b'\xc3\x82\xc2\xab', b'\xc2\xab', 'left guillemet'),
    (b'\xc3\x82\xc2\xbb', b'\xc2\xbb', 'right guillemet'),
    (b'\xc3\x82\xc2\xa7', b'\xc2\xa7', 'section'),
    (b'\xc3\x82\xc2\xb7', b'\xc2\xb7', 'middle dot'),
]


def fix_file(path: Path, dry_run: bool = False) -> tuple[int, int]:
    """Retorna (bytes_reemplazados, num_hits). Si 0 hits, no escribe nada."""
    try:
        data = path.read_bytes()
    except Exception as e:
        print(f"  ERROR leyendo {path}: {e}", file=sys.stderr)
        return (0, 0)

    original = data
    total_hits = 0
    hits_per_type = {}

    for search, replace, desc in REPLACEMENTS:
        count = data.count(search)
        if count > 0:
            data = data.replace(search, replace)
            total_hits += count
            hits_per_type[desc] = count

    if total_hits == 0:
        return (0, 0)

    if not dry_run:
        path.write_bytes(data)

    return (total_hits, len(hits_per_type))


def main():
    parser = argparse.ArgumentParser(description='Repara mojibake UTF-8 en archivos blade/JS')
    parser.add_argument('path', help='Directorio o archivo a procesar')
    parser.add_argument('--dry-run', action='store_true', help='Solo mostrar lo que haria')
    parser.add_argument('--ext', nargs='+', default=['.blade.php', '.js'],
                        help='Extensiones a procesar (default: .blade.php .js)')
    args = parser.parse_args()

    target = Path(args.path)
    if not target.exists():
        print(f"No existe: {target}", file=sys.stderr)
        sys.exit(1)

    # Recolectar archivos
    if target.is_file():
        files = [target]
    else:
        files = []
        for ext in args.ext:
            files.extend(target.rglob(f'*{ext}'))

    files = sorted(set(files))
    print(f"Archivos a escanear: {len(files)}")
    print(f"Modo: {'DRY-RUN' if args.dry_run else 'ESCRITURA'}")
    print()

    total_files_changed = 0
    total_hits = 0
    for f in files:
        hits, types = fix_file(f, dry_run=args.dry_run)
        if hits > 0:
            total_files_changed += 1
            total_hits += hits
            rel = f.relative_to(Path.cwd()) if f.is_relative_to(Path.cwd()) else f
            print(f"  [{hits:>4} hits, {types} tipos] {rel}")

    print()
    print(f"=== Resumen ===")
    print(f"Archivos modificados: {total_files_changed}")
    print(f"Ocurrencias reemplazadas: {total_hits}")
    if args.dry_run:
        print("MODO DRY-RUN: nada modificado en disco")
    else:
        print("OK")


if __name__ == '__main__':
    main()
