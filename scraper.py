import urllib.request
import urllib.parse
import os

url_login = 'http://localhost:8000/login.php'
urls = [
    'http://localhost:8000/member/dashboard.php',
    'http://localhost:8000/member/profile.php',
    'http://localhost:8000/member/update_info.php',
    'http://localhost:8000/member/upload_photo.php',
    'http://localhost:8000/member/surveys.php',
    'http://localhost:8000/member/change_password.php'
]

data = urllib.parse.urlencode({'username': '2026-0002', 'password': '2026-00022', 'login': '1'}).encode('ascii')

req = urllib.request.Request(url_login, data=data)
response = urllib.request.urlopen(req)

cookie = response.getheader('Set-Cookie')
if cookie:
    cookie = cookie.split(';')[0]

os.makedirs('scraped_html', exist_ok=True)

for url in urls:
    req = urllib.request.Request(url)
    if cookie:
        req.add_header('Cookie', cookie)
    res = urllib.request.urlopen(req)
    html = res.read().decode('utf-8')
    name = url.split('/')[-1]
    with open(f'scraped_html/{name}', 'w', encoding='utf-8') as f:
        f.write(html)
    print(f'Fetched {name}')
