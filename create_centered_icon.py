#!/usr/bin/env python3
"""
Create a properly centered app icon for TogetherPay
"""
from PIL import Image, ImageDraw
import sys

def create_foreground_icon(output_path, size=1024):
    """Create white wallet icon on transparent background, centered"""
    # Create transparent image
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Calculate centered position
    # Icon should be 60% of canvas for adaptive icon safe zone
    icon_size = int(size * 0.5)  # 50% of canvas
    padding = (size - icon_size) // 2

    # Draw white rounded rectangle for wallet body
    wallet_left = padding
    wallet_top = padding + int(icon_size * 0.15)
    wallet_right = size - padding
    wallet_bottom = size - padding

    draw.rounded_rectangle(
        [wallet_left, wallet_top, wallet_right, wallet_bottom],
        radius=int(icon_size * 0.1),
        fill=(255, 255, 255, 255)
    )

    # Draw wallet flap (light gray)
    flap_height = int(icon_size * 0.25)
    draw.rounded_rectangle(
        [wallet_left + 20, wallet_top - flap_height + 20, wallet_right - 20, wallet_top + 20],
        radius=int(icon_size * 0.08),
        fill=(224, 224, 224, 255)
    )

    # Draw wallet snap button
    snap_x = wallet_right - int(icon_size * 0.25)
    snap_y = wallet_top + int(icon_size * 0.35)
    snap_radius = int(icon_size * 0.05)
    draw.ellipse(
        [snap_x - snap_radius, snap_y - snap_radius, snap_x + snap_radius, snap_y + snap_radius],
        fill=(200, 200, 200, 255)
    )

    img.save(output_path, 'PNG')
    print(f"Created foreground icon: {output_path}")

def create_full_icon(output_path, size=1024):
    """Create full icon with teal background and white wallet centered"""
    # Create teal circular background
    img = Image.new('RGBA', (size, size), (92, 207, 201, 255))

    # Make it circular by adding alpha mask
    mask = Image.new('L', (size, size), 0)
    draw_mask = ImageDraw.Draw(mask)
    draw_mask.ellipse([0, 0, size, size], fill=255)
    img.putalpha(mask)

    # Create foreground
    fg = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(fg)

    # Draw centered wallet icon (white)
    icon_size = int(size * 0.5)
    padding = (size - icon_size) // 2

    # Wallet body
    wallet_left = padding
    wallet_top = padding + int(icon_size * 0.15)
    wallet_right = size - padding
    wallet_bottom = size - padding

    draw.rounded_rectangle(
        [wallet_left, wallet_top, wallet_right, wallet_bottom],
        radius=int(icon_size * 0.1),
        fill=(255, 255, 255, 255)
    )

    # Wallet flap
    flap_height = int(icon_size * 0.25)
    draw.rounded_rectangle(
        [wallet_left + 20, wallet_top - flap_height + 20, wallet_right - 20, wallet_top + 20],
        radius=int(icon_size * 0.08),
        fill=(224, 224, 224, 255)
    )

    # Wallet snap
    snap_x = wallet_right - int(icon_size * 0.25)
    snap_y = wallet_top + int(icon_size * 0.35)
    snap_radius = int(icon_size * 0.05)
    draw.ellipse(
        [snap_x - snap_radius, snap_y - snap_radius, snap_x + snap_radius, snap_y + snap_radius],
        fill=(200, 200, 200, 255)
    )

    # Composite foreground onto background
    img.paste(fg, (0, 0), fg)

    img.save(output_path, 'PNG')
    print(f"Created full icon: {output_path}")

if __name__ == '__main__':
    base_path = '/Users/amohamed/Herd/togetherpay/mobile/assets/images'

    # Create foreground for adaptive icon
    create_foreground_icon(f'{base_path}/icon_foreground.png', 1024)

    # Create full icon for standard launcher and iOS
    create_full_icon(f'{base_path}/app_icon_centered.png', 1024)

    print("\n✓ Icons created successfully!")
    print("  - icon_foreground.png (transparent background, for adaptive)")
    print("  - app_icon_centered.png (full icon with teal circle)")
