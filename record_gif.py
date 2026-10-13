import asyncio
from playwright.async_api import async_playwright
from PIL import Image
import io
import time
import sys
import os

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 320, "height": 650})
        print("Navigating to login page...")
        await page.goto("http://localhost/cooperative-member-information-system/login.php")
        
        # Wait for the canvas to render
        await page.wait_for_selector("#bg-canvas")
        
        # Hide everything except the canvas
        await page.evaluate('''() => {
            document.querySelectorAll('body > *:not(#bg-canvas)').forEach(el => {
                if(el.tagName !== 'SCRIPT') {
                    el.style.display = 'none';
                }
            });
            // Also ensure the body background is completely transparent or black 
            // so we don't get artifacts from CSS
            document.body.style.background = '#000';
        }''')

        await page.wait_for_timeout(2000)

        FPS = 10
        TOTAL_FRAMES = 30
        DELAY_MS = 1000 // FPS

        frames = []
        
        print("Recording frames...")
        for i in range(TOTAL_FRAMES):
            # Capture screenshot
            buf = await page.screenshot(type="jpeg", quality=60) 
            img = Image.open(io.BytesIO(buf))
            if img.mode != 'RGB':
                img = img.convert('RGB')
            
            # Reduce to 64 colors for GIF optimization
            img = img.quantize(colors=64, method=Image.Quantize.MEDIANCUT)
            
            frames.append(img)
            
            await page.wait_for_timeout(DELAY_MS)
        
        await browser.close()

        print(f"Captured {len(frames)} frames. Optimizing and saving...")
        
        out_file = "space_bg.gif"
        
        # Save GIF
        frames[0].save(
            out_file, 
            save_all=True, 
            append_images=frames[1:], 
            duration=1000//FPS, 
            loop=0, 
            optimize=True
        )
        
        size_mb = os.path.getsize(out_file) / (1024 * 1024)
        print(f"Saved {out_file} - Size: {size_mb:.2f} MB")
        
        if size_mb > 1.9:
            print("File is too large, optimizing further...")
            img_optimized = [img.quantize(colors=32) for img in frames[::2]]
            img_optimized[0].save(
                out_file, 
                save_all=True, 
                append_images=img_optimized[1:], 
                duration=(1000//FPS)*2, 
                loop=0, 
                optimize=True
            )
            size_mb = os.path.getsize(out_file) / (1024 * 1024)
            print(f"Resaved {out_file} - Size: {size_mb:.2f} MB")

if __name__ == "__main__":
    asyncio.run(main())
