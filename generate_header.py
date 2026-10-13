from PIL import Image, ImageDraw

width, height = 300, 140
radius = 16

# 1. Generate vertical gradient image
image = Image.new("RGBA", (width, height))
draw = ImageDraw.Draw(image)

for y in range(height):
    t = y / (height - 1)
    # Define color stops: #00C853 (0, 200, 83) to #371B58 (55, 27, 88) to #1F0833 (31, 8, 51)
    if t < 0.5:
        t2 = t * 2
        r = int(0 + (55 - 0) * t2)
        g = int(200 + (27 - 200) * t2)
        b = int(83 + (88 - 83) * t2)
    else:
        t2 = (t - 0.5) * 2
        r = int(55 + (31 - 55) * t2)
        g = int(27 + (8 - 27) * t2)
        b = int(88 + (51 - 88) * t2)
    draw.line([(0, y), (width, y)], fill=(r, g, b, 255))

# 2. Create high-res mask for smooth anti-aliased corners
scale = 4
w, h = width * scale, height * scale
r_scaled = radius * scale

mask = Image.new("L", (w, h), 255)
mask_draw = ImageDraw.Draw(mask)

# Top-left corner: draw black square, then white circle over it
mask_draw.rectangle([0, 0, r_scaled, r_scaled], fill=0)
mask_draw.pieslice([0, 0, r_scaled * 2, r_scaled * 2], 180, 270, fill=255)

# Top-right corner
mask_draw.rectangle([w - r_scaled, 0, w, r_scaled], fill=0)
mask_draw.pieslice([w - r_scaled * 2, 0, w, r_scaled * 2], 270, 360, fill=255)

# Downscale mask for anti-aliasing
mask = mask.resize((width, height), Image.Resampling.LANCZOS)

# Apply mask
image.putalpha(mask)

# Save
image.save("header_gradient.png")
print("Saved header_gradient.png")
