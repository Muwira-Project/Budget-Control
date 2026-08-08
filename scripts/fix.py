import re

p = "tests/Feature/DashboardTest.php"
with open(p, "r", encoding="utf-8") as f:
    s = f.read()

s = re.sub(r"->call\('set', '(\w+)', '([^']+)'\)", r"->set('\1', '\2')", s)

with open(p, "w", encoding="utf-8") as f:
    f.write(s)

print("done")
