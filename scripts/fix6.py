import re

p = "tests/Feature/DashboardTest.php"
with open(p, "r", encoding="utf-8") as f:
    s = f.read()

old = """        $project = Project::factory()->create(['status' => 'completed', 'qty' => 1, 'harga_satuan' => 100000000]);
        Payable::create(['project_id' => $project->id, 'akun_id' => Akun::factory()->create()->id, 'tanggal' => '2026-01-01', 'nominal' => 30000000]);"""

new = """        $project = Project::factory()->create(['status' => 'completed', 'qty' => 1, 'harga_satuan' => 100000000]);
        $akun = Akun::factory()->create();
        $supplier = \\\\App\\\\Models\\\\Supplier::factory()->create();
        Payable::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'tanggal' => '2026-01-01', 'nominal' => 30000000, 'supplier_id' => $supplier->id, 'vendor_id' => null]);"""

s = s.replace(old, new)
with open(p, "w", encoding="utf-8") as f:
    f.write(s)

print("done")
