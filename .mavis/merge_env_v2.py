#!/usr/bin/env python3
"""Mergea .env-mercadopago en .env, sin duplicados case-insensitive. Robusto."""
import sys
from pathlib import Path

env_path = Path(sys.argv[1])
mp_path = Path(sys.argv[2])


def parse_env(path):
    """Parsea un .env. Devuelve dict {KEY_UPPER: (key_original, 'KEY=value')} (ultimo gana)."""
    result = {}
    if not path.exists():
        return result
    for line in path.read_text(encoding='utf-8').splitlines():
        stripped = line.strip()
        if not stripped or stripped.startswith('#'):
            continue
        if '=' not in stripped:
            continue
        key = stripped.split('=', 1)[0].strip()
        value = stripped.split('=', 1)[1]
        key_upper = key.upper()
        result[key_upper] = (key, value)
    return result


def normalize_line(key_upper, key_orig, value):
    """Devuelve 'KEY_UPPER=value' con la key en mayusculas."""
    return f"{key_upper}={value}"


# 1. Parsear ambos
env_vars = parse_env(env_path)
mp_vars = parse_env(mp_path)

# 2. Mezclar (mp_vars sobrescribe env_vars, ambos con keys en mayusculas)
merged = {**env_vars, **mp_vars}

# 3. Preservar el orden del .env original, agregando las nuevas al final
order = []
if env_path.exists():
    for line in env_path.read_text(encoding='utf-8').splitlines():
        stripped = line.strip()
        if not stripped or stripped.startswith('#') or '=' not in stripped:
            continue
        key_upper = stripped.split('=', 1)[0].strip().upper()
        if key_upper in order:
            continue  # skip duplicados
        order.append(key_upper)

# Agregar keys de MP que no estaban en .env
for key in mp_vars:
    if key not in order:
        order.append(key)

# 4. Reconstruir
out_lines = []
for key in order:
    if key in merged:
        key_orig, value = merged[key]
        out_lines.append(normalize_line(key, key_orig, value))

env_path.write_text('\n'.join(out_lines) + '\n', encoding='utf-8')

# 5. Reporte
print(f"Total keys en .env final: {len(merged)}")
print(f"Keys de MP aplicadas: {len(mp_vars)}")
print(f"Keys de MP duplicadas en .env (reemplazadas): {sum(1 for k in mp_vars if k in env_vars)}")
print(f"Keys de MP nuevas (agregadas): {sum(1 for k in mp_vars if k not in env_vars)}")
print(f"Keys de MP finales (uppercase): {list(mp_vars.keys())}")
