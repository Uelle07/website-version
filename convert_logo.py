import asyncio
from playwright.async_api import async_playwright
import os

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 420, "height": 100})
        
        pwd = os.getcwd().replace('\\', '/')
        svg_path = f"{pwd}/assets/images/space-brand-logo.svg"
        
        with open(svg_path, 'r', encoding='utf-8') as f:
            svg_content = f.read()
            
        html = f"""
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {{
                    margin: 0;
                    padding: 0;
                    background: transparent !important;
                    overflow: hidden;
                }}
                svg {{
                    display: block;
                }}
            </style>
        </head>
        <body>
            {svg_content}
        </body>
        </html>
        """
        
        await page.set_content(html)
        
        out_file = "space_brand_logo.png"
        
        # Take full page screenshot with omit_background which guarantees alpha channel
        await page.screenshot(path=out_file, omit_background=True, full_page=True)
        
        await browser.close()
        print(f"Exported {out_file} with transparent background")

if __name__ == "__main__":
    asyncio.run(main())
