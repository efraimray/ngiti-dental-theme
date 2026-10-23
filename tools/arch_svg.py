"""Generate the hero dental-arch illustration (inline SVG, theme colour tokens).

Run: python3 tools/arch_svg.py > template-parts/hero-arch.svg.txt
Used by template-parts/hero-arch.php and tools/build-demo.py.
"""
import math

# FDI numbers for the upper arch, from the patient's right (18) to left (28).
UPPER = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28]
SIZE = {1: 21, 2: 18, 3: 21, 4: 23, 5: 23, 6: 31, 7: 29, 8: 26}  # half-width by tooth type


def arch_svg(highlight=16):
    cx, cy, rx, ry = 230, 290, 160, 220
    parts = [
        '<svg class="arch" viewBox="0 0 460 400" role="img" aria-labelledby="arch-title">',
        '<title id="arch-title">Upper dental arch with FDI tooth numbers</title>',
    ]
    pts = []
    for i in range(len(UPPER)):
        ang = math.radians(200 - i / (len(UPPER) - 1) * 220)
        pts.append((cx + rx * math.cos(ang), cy - ry * math.sin(ang) * 0.92))
    # gum band behind the teeth: a thick rounded line through the tooth centres
    parts.append('<polyline points="%s" fill="none" stroke="var(--blush)" stroke-width="54" stroke-linecap="round" stroke-linejoin="round"/>'
                 % " ".join("%.1f,%.1f" % p for p in pts))
    n = len(UPPER)
    for i, fdi in enumerate(UPPER):
        # angle from 200deg (right side, low) to -20deg (left side, low), through 90 (front)
        t = i / (n - 1)
        ang = math.radians(200 - t * 220)
        x = cx + rx * math.cos(ang)
        y = cy - ry * math.sin(ang) * 0.92
        w = SIZE[fdi % 10]
        h = w * 0.82
        rot = -math.degrees(ang) + 90
        hl = fdi == highlight
        fill = "var(--brand)" if hl else "var(--surface)"
        parts.append(
            '<g transform="translate(%.1f %.1f) rotate(%.1f)"><rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="%.1f" fill="%s" stroke="var(--brand)" stroke-width="2.2"/></g>'
            % (x, y, rot, -w / 2, -h / 2, w, h, w * 0.42, fill)
        )
        lx = cx + (rx + 52) * math.cos(ang)
        ly = cy - (ry + 40) * math.sin(ang) * 0.92 + 4
        parts.append(
            '<text x="%.1f" y="%.1f" text-anchor="middle" font-size="12" font-weight="600" fill="%s" font-family="Figtree, system-ui, sans-serif">%d</text>'
            % (lx, ly, "var(--brand)" if hl else "var(--muted)", fdi)
        )
    parts.append(
        '<text x="230" y="392" text-anchor="middle" font-size="13" fill="var(--muted)" font-family="Figtree, system-ui, sans-serif" letter-spacing="1.5">UPPER ARCH · FDI NUMBERING</text>'
    )
    parts.append("</svg>")
    return "".join(parts)


if __name__ == "__main__":
    print(arch_svg())
