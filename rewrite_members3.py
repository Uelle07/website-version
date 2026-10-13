with open('C:/xampp/htdocs/cooperative-member-information-system/staff/members.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('require_staff();', '')

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/members.php', 'w', encoding='utf-8') as f:
    f.write(content)
