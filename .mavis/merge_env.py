#!/usr/bin/env python3
"""Mergea .env-mercadopago en .env, evitando duplicados."""
import sys
from pathlib import Path

env_path = Path(sys.argv[1])
mp_path = Path(sys.argv[2])

# Parsear .env existente
existing = {}
order = []
if env_path.exists():
    for line in env_path.read_text(encoding='utf-8').splitlines():
        line_strip = line.strip()
        if not line_strip or line_strip.startswith('#'):
            order.append(('comment', line))
            continue
        if '=' in line:
            key = line.split('=', 1)[0].strip()
            existing[key] = line
            order.append(('var', key))

# Parsear .env-mercadopago
mp_vars = {}
for line in mp_path.read_text(encoding='utf-8').splitlines():
    line_strip = line.strip()
    if not line_strip or line_strip.startswith('#'):
        continue
    if '=' in line:
        key = line.split('=', 1)[0].strip()
        # Normalizar keys de Mercado Pago a MAYUSCULAS (Laravel es case-sensitive)
        if key.lower().startswith('mp_') or key.lower().startswith('url_mp'):
            key = key.upper()
        elif key.lower() in ('mp_clave_webhook', 'mp_public_key', 'mp_access_token'):
            key = key.upper()
        mp_vars[key] = line

# Mezclar: las del .env-mercadopago sobrescriben o se agregan
new_vars = {**existing, **mp_vars}

# Reconstruir el archivo
out_lines = []
seen_keys = set()
for entry in order:
    if entry[0] == 'comment':
        out_lines.append(entry[1])
    else:
        key = entry[1]
        if key in seen_keys:
            continue
        seen_keys.add(key)
        out_lines.append(existing[key])

# Agregar las nuevas que no existian
for key, line in mp_vars.items():
    if key not in seen_keys:
        out_lines.append(line)
        seen_keys.add(key)

env_path.write_text('\n'.join(out_lines) + '\n', encoding='utf-8')
print(f"Merged: {len(seen_keys)} total vars ({len(mp_vars)} from mercadopago)")
print(f"Keys from MP: {list(mp_vars.keys())}")
print(f"Keys duplicated (replaced): {[k for k in mp_vars if k in existing]}")
print(f"Keys added (new): {[k for k in mp_vars if k not in existing]}")
