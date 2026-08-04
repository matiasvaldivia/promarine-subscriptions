"""Generate lightweight WebP derivatives used by the public landing page."""

from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "public" / "assets" / "promarine"
OUTPUT = ASSETS / "optimized"

JOBS = [
    (ASSETS / "brand" / "promarine-logo-white-web.png", OUTPUT / "promarine-logo-300.webp", 300),
    (ASSETS / "brand" / "promarine-sea-urchin-mark-white.png", OUTPUT / "promarine-urchin-320.webp", 320),
    (ASSETS / "institutions" / "conicet-logo-color.png", OUTPUT / "trust" / "conicet-240.webp", 240),
]

for slug in ("marine-epic", "marine-fusion", "echa-marine", "marine-pulse"):
    JOBS.extend([
        (ASSETS / "products" / f"{slug}-composition-portrait.png", OUTPUT / f"{slug}-composition-480.webp", 480),
        (ASSETS / "demo" / f"{slug}-bottle.png", OUTPUT / f"{slug}-bottle-480.webp", 480),
        (ASSETS / "demo" / f"{slug}-box.png", OUTPUT / f"{slug}-box-480.webp", 480),
    ])

for stem in (
    "seal-gmp",
    "seal-heavy-metals-tested",
    "seal-non-gmo",
    "seal-cruelty-free",
    "seal-gluten-free",
    "seal-clinically-tested",
    "icon-respiratory-support",
):
    JOBS.append((ASSETS / "demo" / f"{stem}.png", OUTPUT / "trust" / f"{stem}-240.webp", 240))


def optimize(source: Path, destination: Path, max_width: int) -> tuple[int, int]:
    if not source.is_file():
        raise FileNotFoundError(source)

    destination.parent.mkdir(parents=True, exist_ok=True)
    original_bytes = source.stat().st_size

    with Image.open(source) as image:
        image.thumbnail((max_width, max_width * 2), Image.Resampling.LANCZOS)
        image.save(destination, "WEBP", quality=84, method=6, exact=True)

    return original_bytes, destination.stat().st_size


def main() -> None:
    original_total = 0
    optimized_total = 0

    for source, destination, max_width in JOBS:
        original, optimized = optimize(source, destination, max_width)
        original_total += original
        optimized_total += optimized
        print(f"{destination.relative_to(ROOT)}: {original / 1024:.1f} KB -> {optimized / 1024:.1f} KB")

    reduction = 100 - ((optimized_total / original_total) * 100)
    print(f"Total: {original_total / 1024 / 1024:.2f} MB -> {optimized_total / 1024 / 1024:.2f} MB ({reduction:.1f}% menos)")


if __name__ == "__main__":
    main()
