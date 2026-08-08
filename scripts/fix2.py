import re

p = "tests/Feature/DashboardTest.php"
with open(p, "r", encoding="utf-8") as f:
    s = f.read()

s = s.replace(
    "Payable::create([\n            'project_id' => $project->id, 'akun_id' => Akun::factory()->create()->id, 'tanggal' => '2026-01-01', 'nominal' => 30000000,\n        ]);",
    "Payable::create([\n            'project_id' => $project->id, 'akun_id' => Akun::factory()->create()->id, 'tanggal' => '2026-01-01', 'nominal' => 30000000,\n            'mandor_id' => \\\\App\\\\Models\\\\Mandor::factory()->id,\n        ]);"
)

with open(p, "w", encoding="utf-8") as f:
    f.write(s)

print("done")
