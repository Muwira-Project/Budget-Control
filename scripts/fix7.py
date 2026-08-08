p = "tests/Feature/DashboardTest.php"
with open(p, "r", encoding="utf-8") as f:
    s = f.read()

s = s.replace(
    "        $akun = Akun::factory()->create();\n        $supplier = \\\\App\\\\Models\\\\Supplier::factory()->create();\n        Payable::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-01', 'nominal' => 30000000, 'supplier_id' => $supplier->id, 'vendor_id' => null]);",
    ""
)

with open(p, "w", encoding="utf-8") as f:
    f.write(s)

print("done")
