"""
Favicon Generator for KaiShop
Generates all necessary favicon sizes from a master image with Border Radius
"""

from PIL import Image, ImageDraw, ImageOps
import os

# Paths - Ảnh mới upload
MASTER_IMAGE = r"C:\Users\Phucc\.gemini\antigravity\brain\4afd4f1c-f176-41cb-8360-bfce0d71155b\uploaded_image_1767932116577.png"
OUTPUT_DIR = r"c:\xampp\htdocs\kaishop\assets\images\favicons"

# Create output directory if it doesn't exist
os.makedirs(OUTPUT_DIR, exist_ok=True)

# Favicon sizes to generate
SIZES = {
    'favicon-16x16.png': (16, 16),
    'favicon-32x32.png': (32, 32),
    'favicon-48x48.png': (48, 48),
    'apple-touch-icon.png': (180, 180),
    'android-chrome-192x192.png': (192, 192),
    'android-chrome-512x512.png': (512, 512),
}

def make_square_rounded(img, size, radius_px=None):
    """
    Resize, Crop center square, và Bo góc
    radius_px: Số pixel bo góc (ở kích thước target). Nếu None thì tự tính theo tỷ lệ ~3px/32px.
    """
    # 1. Resize & Crop Center thành hình vuông trước
    # Tính tỷ lệ để crop
    target_size = size
    width, height = img.size
    
    # Lấy phần giữa hình vuông
    new_dim = min(width, height)
    left = (width - new_dim)/2
    top = (height - new_dim)/2
    right = (width + new_dim)/2
    bottom = (height + new_dim)/2
    
    img_cropped = img.crop((left, top, right, bottom))
    
    # Resize về kích thước đích (LANCZOS for best quality)
    img_resized = img_cropped.resize(target_size, Image.Resampling.LANCZOS)
    
    # 2. Xử lý Bo Góc
    # Tính radius: User muốn "3px" (thường ám chỉ ở size 32x32 hoặc visual perception).
    # Tỷ lệ: 3 / 32 ~= 0.09375.
    if radius_px is None:
        radius = max(2, int(target_size[0] * 0.1)) 
    else:
        radius = radius_px

    # Tạo mask bo góc
    mask = Image.new("L", target_size, 0)
    draw = ImageDraw.Draw(mask)
    
    # Vẽ hình chữ nhật bo góc vào mask (màu trắng)
    # xy needs to be bounding box from (0,0) to(width, height)
    draw.rounded_rectangle([(0, 0), target_size], radius=radius, fill=255)
    
    # Áp dụng mask
    output = ImageOps.fit(img_resized, mask.size, centering=(0.5, 0.5))
    output.putalpha(mask)
    
    return output

def generate_favicons():
    print("🎨 KaiShop Favicon Generator - Squared Style (New Image)")
    print("=" * 50)
    
    print(f"\n📂 Loading master image...")
    try:
        master = Image.open(MASTER_IMAGE)
        if master.mode != 'RGBA':
            master = master.convert('RGBA')
        print(f"✅ Loaded: {master.size}")
    except Exception as e:
        print(f"❌ Error: {e}")
        return
    
    print(f"\n🔧 Processing 'Vuông Vuông' (Border Radius ~3px scale)...")
    
    generated_images = {}
    
    for filename, size in SIZES.items():
        output_path = os.path.join(OUTPUT_DIR, filename)
        
        # Xử lý ảnh: Resize + Bo góc
        # Tỷ lệ scale dựa trên 3px ở size 32
        scale_ratio = 3.0 / 32.0
        calculated_radius = int(size[0] * scale_ratio)
        current_rad = max(2, calculated_radius) # Tối thiểu 2px
        
        new_icon = make_square_rounded(master, size, radius_px=current_rad)
        
        new_icon.save(output_path, 'PNG', optimize=True, quality=95)
        generated_images[size] = new_icon # Save for ICO
        print(f"✅ {filename} ({size[0]}x{size[1]}) - Radius: {current_rad}px")
        
    # Generate ICO
    print(f"\n🔧 Generating favicon.ico...")
    ico_path = os.path.join(OUTPUT_DIR, 'favicon.ico')
    ico_imgs = [generated_images[(16,16)], generated_images[(32,32)], generated_images[(48,48)]]
    ico_imgs[0].save(ico_path, format='ICO', sizes=[(16,16), (32,32), (48,48)], append_images=ico_imgs[1:])
    print(f"✅ Generated favicon.ico")

    print("\n✨ Done! Favicon mới từ ảnh upload đã sẵn sàng.")

if __name__ == "__main__":
    generate_favicons()
