import re

p = "tests/Feature/DashboardTest.php"
with open(p, "r", encoding="utf-8") as f:
    s = f.read()

s = s.replace(
    "$akun = Akun::factory()->create();\n        Payable::create(['project_id' => $project->id, 'akun_id' => Akun::factory()->create()->id",
    "$akun = Akun::factory()->create();\n        Payable::factory()->forSupplier()->create(['project_id' => $project->id, 'akun_id' => Akun::factory()->create()->id"
)

with open(p, "w", encoding="utf-8") as f:
    f.write(s)

print("done")
