# Implementation Plan — 2026-10-11 Critique Fixes

**Status:** Approved (User feedback alignment complete) | **Branch:** `feature/cash-non-project-ap-ar`  
**Commits:** 6 atomic commits | **Migration:** 2 new + seeder updates  
**Tests:** 8+ feature tests + 4 existing test updates  
**Rollback:** Sequential via `git revert` + `php artisan migrate:rollback`

---

## User Critiques & Alignment

| # | Critique | Finding | Fix | Owner Commit |
|---|----------|---------|-----|--------------|
| 1 | Void button tidak terlihat untuk delete cash out | Icon-only h-8 w-8 button, gray styling, invisible | Expand to labeled button (h-10 px-3), red/amber color, text label "Void" | Commit 6 (Views) |
| 2 | Tambah diskon, PPH, PPN di AR/AP, bisa set manual | AR=no tax field, AP=has jenis_pajak | Add 4 cols receivables (jenis_pajak, discount_percent, pajak_amount, readonly); Add 2 cols payables (discount_percent, pajak_amount readonly) | Commits 1,2,5 |
| 3 | Hapus type good and service | ProjectJenis enum + jenis column | Drop enum, drop column, remove tax defaults, set pajak=0 (manual input) | Commits 1,2,3,4 |
| 4 | Perbaiki tampilan billed, unbilled, inprogress lebih rapih | Dense table, hard to scan | Tab-based design: Billed \| Unbilled \| In Progress with summary card per tab | Commit 6 (Views) |
| 5 | Import tanpa PO masuk unbilled, proses jalan, po bisa diisi belakangan | Blocked by po_number validation | Allow null po_number during import; require only on status→Done transition | Commits 1,4 |

---

## 6-Commit Delivery Plan

### **Commit 1: Database Migrations + Rollback**

**Files:**
- `database/migrations/2026_10_11_HHMMSS_add_tax_discount_to_receivables_payables.php` (NEW)
- `database/migrations/2026_10_11_HHMMSS_drop_jenis_from_projects.php` (NEW)

**Schema Changes:**

```sql
-- Migration 1: Add tax/discount columns (Critique #2, #5)
ALTER TABLE receivables ADD COLUMN jenis_pajak VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE receivables ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE receivables ADD COLUMN pajak_amount DECIMAL(15,2) NOT NULL DEFAULT 0;

ALTER TABLE payables ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE payables ADD COLUMN pajak_amount DECIMAL(15,2) NOT NULL DEFAULT 0;

-- Migration 2: Drop jenis from projects (Critique #3)
ALTER TABLE projects DROP COLUMN jenis;
```

**Rollback Strategy:**
- M1 reverse: `DROP COLUMN` all 5 added columns
- M2 reverse: `ADD COLUMN jenis VARCHAR(20)` with backfill from audit log or default 'barang'
- Command: `php artisan migrate:rollback --step=2`

**Verification:**
- `php artisan tinker`: `dd(DB::select('PRAGMA table_info(receivables)'))`
- Check: 5 columns exist with correct types
- Check: projects.jenis removed

**Impact:** Receivable + Payable models updated (next commit), forms updated (commit 5)

---

### **Commit 2: Models + Enums**

**Files:**
- `app/Enums/ProjectJenis.php` — **DELETE** (Critique #3)
- `app/Models/Receivable.php` — update fillable + casts (Critique #2)
- `app/Models/Payable.php` — update fillable + casts (Critique #2)
- `app/Models/Project.php` — remove jenis cast (Critique #3)

**Changes:**

```php
// app/Models/Receivable.php
#[Fillable(['project_id', 'pihak_type_id', 'pihak_item_id', 'tanggal', 'nomor_invoice', 'jatuh_tempo', 'nominal', 'nominal_dibayar', 'keterangan', 'hold_reason', 'held_by', 'held_at', 'jenis_pajak', 'discount_percent', 'pajak_amount'])]

protected function casts(): array {
    return [
        'tanggal' => 'date',
        'jatuh_tempo' => 'date',
        'nominal' => 'decimal:2',
        'nominal_dibayar' => 'decimal:2',
        'jenis_pajak' => 'string',
        'discount_percent' => 'decimal:2',
        'pajak_amount' => 'decimal:2',
        'held_at' => 'datetime',
    ];
}

// app/Models/Payable.php
#[Fillable(['project_id', 'realisasi_id', 'akun_id', 'pihak_type_id', 'pihak_item_id', 'tanggal', 'nomor_invoice', 'jatuh_tempo', 'nominal', 'jenis_pajak', 'pajak_include', 'nominal_dibayar', 'keterangan', 'discount_percent', 'pajak_amount'])]

protected function casts(): array {
    return [
        'tanggal' => 'date',
        'jatuh_tempo' => 'date',
        'nominal' => 'decimal:2',
        'nominal_dibayar' => 'decimal:2',
        'jenis_pajak' => PajakJenis::class,
        'pajak_include' => 'boolean',
        'discount_percent' => 'decimal:2',
        'pajak_amount' => 'decimal:2',
    ];
}

// app/Models/Project.php
// REMOVE: use App\Enums\ProjectJenis;
// In casts() remove: 'jenis' => ProjectJenis::class,
```

**Verification:**
- `php artisan tinker`: Create model instances with new fields
- No errors on mass-assignment or type casting

**Impact:** Services + validation (commit 3-4), forms (commit 5)

---

### **Commit 3: Services + Business Logic**

**Files:**
- `app/Services/DashboardStatisticsService.php` — remove projects_barang/jasa count
- `app/Services/PayableService.php` — verify pajak_amount calc (likely no change)
- `app/Services/ReceivableService.php` — verify billed logic (likely no change)

**Changes:**

```php
// app/Services/DashboardStatisticsService.php — line 131-132 DELETE:
// 'projects_barang' => $projects->where('jenis', ProjectJenis::Barang)->count(),
// 'projects_jasa' => $projects->where('jenis', ProjectJenis::Jasa)->count(),

// Also remove import:
// use App\Enums\ProjectJenis;
```

**Verification:**
- Test: `DashboardStatisticsService::computeStatistics()` does NOT return `projects_barang` or `projects_jasa` keys
- Dashboard renders without errors

**Impact:** Dashboard display (no direct UI impact), test suite (commit 6 testing)

---

### **Commit 4: Validation + Imports/Exports**

**Files:**
- `app/Http/Requests/Project/StoreProjectRequest.php` — remove jenis validation
- `app/Http/Requests/Project/UpdateProjectRequest.php` — remove jenis validation
- `app/Models/Project.php` — add po_number validation on status→Done
- `app/Imports/ProjectImport.php` — remove jenis handling
- `app/Imports/ReceivableImport.php` — add jenis_pajak + discount_percent mapping (optional)
- `app/Imports/PayableImport.php` — add discount_percent mapping (optional)
- `app/Exports/ProjectExport.php` — remove jenis from map
- `app/Exports/ReceivableExport.php` — add 3 new cols to map
- `app/Exports/PayableExport.php` — add 2 new cols to map (if exists)

**Changes:**

```php
// StoreProjectRequest.php + UpdateProjectRequest.php
// DELETE rule:
// 'jenis' => ['required', Rule::in(['barang', 'jasa'])],

// app/Models/Project.php — add po_number validation in booted():
static::updating(function (Project $project) {
    if ($project->isDirty('status') && $project->status === ProjectStatus::Done) {
        if (blank($project->po_number)) {
            throw ValidationException::withMessages([
                'po_number' => 'PO Number wajib diisi saat status Done.',
            ]);
        }
    }
});

// ProjectImport.php — expectedHeaders: REMOVE 'type'
protected array $expectedHeaders = ['code', 'name', 'location', 'division', 'pic', 'project_category', 'sub_work', 'period', 'qty', 'unit', 'unit_price', 'tax', 'start_date', 'target_finish', 'status'];

// validateRow() — DELETE lines 36-42:
// $jenisRaw = trim(...);
// $jenis = $this->normalizeJenis($jenisRaw);
// if ($jenis === '') { $jenis = 'barang'; }

// ReceivableImport.php — validateRow() add optional:
$jenisPajak = trim((string) $row->get('jenis_pajak')) ?: null;
$discountPercent = $this->normalizeNominal($row->get('discount_percent')) ?? 0;

// Include in return array:
'jenis_pajak' => $jenisPajak,
'discount_percent' => $discountPercent,

// ProjectExport.php — line 50 DELETE:
// $project->jenis->value,

// ReceivableExport.php — update headings + map:
// Add to headings: 'Tax Type', 'Discount %', 'Tax Amount'
// Add to map: $receivable->jenis_pajak, $receivable->discount_percent, $receivable->pajak_amount
```

**Verification:**
- ProjectImport: validate header check passes (no 'type' col required)
- ReceivableImport: map new optional cols
- ProjectExport: output columns correct (no jenis)
- Project status→Done without po_number throws ValidationException
- Test: ProjectStatusTransitionTest::test_done_requires_po_number PASSES

**Impact:** Import/export views (no change), forms accept new data (commit 5)

---

### **Commit 5: Forms + Livewire Components**

**Files:**
- `app/Livewire/Projects/Create.php` — remove jenis + auto-tax logic
- `app/Livewire/Projects/Edit.php` — remove jenis + auto-tax logic
- `app/Livewire/Receivables/Create.php` — add tax + discount (computed readonly pajax_amount)
- `app/Livewire/Receivables/Edit.php` — add tax + discount
- `app/Livewire/Payables/Create.php` — add discount
- `app/Livewire/Payables/Edit.php` — add discount
- `resources/views/livewire/projects/_form.blade.php` — remove jenis select
- `resources/views/livewire/receivables/_form.blade.php` — add tax + discount inputs
- `resources/views/livewire/payables/_form.blade.php` — add discount input

**Changes:**

```php
// app/Livewire/Projects/Create.php + Edit.php
// DELETE property: public $jenis;
// DELETE line: $this->pajak = (string) ProjectJenis::from($this->jenis)->pajakDefault();
// Replace with: $this->pajak = '0'; // User must input manually

// app/Livewire/Receivables/Create.php + Edit.php
public ?string $jenisPajak = null;
public string $discountPercent = '0';

#[Computed]
public function pajaxAmount(): float {
    if (blank($this->nominal)) return 0;
    $base = (float) $this->nominal;
    $discount = $base * ((float) $this->discountPercent / 100);
    $afterDiscount = $base - $discount;
    
    // Use project tax if project selected, else use jenis_pajak
    if ($this->projectId) {
        $taxRate = (float) ($this->project?->pajak ?? 0);
    } else {
        $taxRate = $this->jenisPajak === 'ppn' ? 11 
                 : ($this->jenisPajak === 'pph' ? 2 : 0);
    }
    return $afterDiscount * ($taxRate / 100);
}

// Save method must include new fields:
'jenis_pajak' => $this->jenisPajak,
'discount_percent' => (float) $this->discountPercent,
'pajak_amount' => $this->pajaxAmount,

// app/Livewire/Payables/Create.php + Edit.php
public string $discountPercent = '0';

#[Computed]
public function pajaxAmount(): float {
    if (blank($this->nominal)) return 0;
    $base = (float) $this->nominal;
    $discount = $base * ((float) $this->discountPercent / 100);
    $afterDiscount = $base - $discount;
    
    $taxRate = $this->jenisPajak === 'ppn' ? 11 
             : ($this->jenisPajak === 'pph' ? 2 : 0);
    
    if ($this->pajakInclude) {
        return $afterDiscount - ($afterDiscount / (1 + $taxRate / 100));
    }
    return $afterDiscount * ($taxRate / 100);
}

// Save: add new fields
'discount_percent' => (float) $this->discountPercent,
'pajak_amount' => $this->pajaxAmount,
```

**Blade Changes:**

```blade
{{-- resources/views/livewire/projects/_form.blade.php --}}
{{-- DELETE entire jenis dropdown section (lines ~1-20) --}}

{{-- resources/views/livewire/receivables/_form.blade.php --}}
{{-- Add after nominal field --}}
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="jenis_pajak" :value="__('Tax Type (optional)')"/>
        <select id="jenis_pajak" wire:model.live="jenisPajak" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">None</option>
            <option value="ppn">PPN</option>
            <option value="pph">PPh</option>
        </select>
    </div>
    <div>
        <x-input-label for="discount_percent" :value="__('Discount %')"/>
        <x-text-input id="discount_percent" class="mt-1 block w-full" type="text" wire:model.live="discountPercent" placeholder="0" />
    </div>
</div>

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
    <p class="text-sm text-gray-700">
        <strong>Tax Amount (auto-calculated):</strong> <span class="text-blue-600 font-semibold">Rp {{ format_idr($this->pajaxAmount) }}</span>
    </p>
</div>

{{-- resources/views/livewire/payables/_form.blade.php --}}
{{-- Add discount input before jenis_pajak section (after nominal field) --}}
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="discount_percent" :value="__('Discount %')"/>
        <x-text-input id="discount_percent" class="mt-1 block w-full" type="text" wire:model.live="discountPercent" placeholder="0" />
    </div>
    {{-- jenis_pajak already exists (no change to that section) --}}
</div>

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
    <p class="text-sm text-gray-700">
        <strong>Tax Amount (auto-calculated):</strong> <span class="text-blue-600 font-semibold">Rp {{ format_idr($this->pajaxAmount) }}</span>
    </p>
</div>
```

**Verification:**
- Create project: no jenis field, pajak requires manual input
- Create receivable: discount input works, jenis_pajak dropdown works, pajax_amount updates on change
- Edit receivable: discount/tax change updates pajax_amount
- Create payable: discount input appears, pajax_amount auto-calcs

**Impact:** Data saved to database (commit 1 migrations), displayed in views (commit 6)

---

### **Commit 6: Views + Button Redesign + Tab Layout**

**Files:**
- `resources/views/livewire/receivables/index.blade.php` — redesign: tabs (Billed/Unbilled/InProgress) with summary (Critique #4)
- `resources/views/livewire/cashflows/index.blade.php` — fix void button visibility (Critique #1)
- `resources/views/livewire/payments/index.blade.php` — fix void button visibility
- `resources/views/livewire/projects/index.blade.php` — remove jenis badge

**Changes:**

```blade
{{-- resources/views/livewire/receivables/index.blade.php --}}
{{-- Add tabs above existing filter/table section --}}
<div class="mb-6">
    <div class="border-b border-gray-200 flex gap-8">
        <button 
            wire:click="$set('arCategory', 'billed')"
            class="px-4 py-3 text-sm font-medium transition {{ $this->arCategory === 'billed' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">
            Billed ({{ $this->arSummary['billed']['count'] ?? 0 }})
        </button>
        <button 
            wire:click="$set('arCategory', 'unbilled')"
            class="px-4 py-3 text-sm font-medium transition {{ $this->arCategory === 'unbilled' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">
            Unbilled ({{ $this->arSummary['unbilled']['count'] ?? 0 }})
        </button>
        <button 
            wire:click="$set('arCategory', 'inprogress')"
            class="px-4 py-3 text-sm font-medium transition {{ $this->arCategory === 'inprogress' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900' }}">
            In Progress ({{ $this->arSummary['inprogress']['count'] ?? 0 }})
        </button>
    </div>
</div>

{{-- Summary card per tab --}}
@php
    $catData = $this->arSummary[$this->arCategory] ?? ['count' => 0, 'total' => 0, 'paid' => 0, 'outstanding' => 0];
@endphp
<div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
    <div class="grid grid-cols-4 gap-4 text-sm">
        <div>
            <p class="text-gray-600">Count</p>
            <p class="text-lg font-semibold text-blue-600">{{ $catData['count'] }}</p>
        </div>
        <div>
            <p class="text-gray-600">Total</p>
            <p class="text-lg font-semibold text-blue-600">{{ format_idr($catData['total'] ?? 0) }}</p>
        </div>
        <div>
            <p class="text-gray-600">Paid</p>
            <p class="text-lg font-semibold text-green-600">{{ format_idr($catData['paid'] ?? 0) }}</p>
        </div>
        <div>
            <p class="text-gray-600">Outstanding</p>
            <p class="text-lg font-semibold text-red-600">{{ format_idr($catData['outstanding'] ?? 0) }}</p>
        </div>
    </div>
</div>

{{-- Existing filter + table (no change, filtered by arCategory) --}}

{{-- resources/views/livewire/cashflows/index.blade.php --}}
{{-- Line 206-213: Replace void button --}}
{{-- BEFORE: --}}
<button wire:click="approveVoid(...)" title="Void Settlement (Admin)" 
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-red-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700">
    <x-icon name="x-circle" class="h-4 w-4" />
</button>

{{-- AFTER: --}}
<button wire:click="approveVoid(...)" title="Void Settlement (Admin)"
        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition">
    <x-icon name="x-circle" class="h-4 w-4" />
    <span>Void</span>
</button>

{{-- Similarly for requestVoid() button at line 211 (staff request): --}}
<button wire:click="requestVoid(...)" title="Request Void Settlement"
        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition">
    <x-icon name="x-mark" class="h-4 w-4" />
    <span>Request Void</span>
</button>

{{-- resources/views/livewire/payments/index.blade.php --}}
{{-- Same fix pattern as cashflows: expand icon buttons to labeled buttons with red/amber color --}}

{{-- resources/views/livewire/projects/index.blade.php --}}
{{-- DELETE jenis badge entirely (line ~96) --}}
{{-- BEFORE: --}}
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $project->jenis === ProjectJenis::Jasa ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
    {{ $project->jenis === ProjectJenis::Jasa ? 'Services' : 'Goods' }}
</span>

{{-- AFTER: (deleted entirely) --}}
```

**Verification:**
- Receivable index: tabs clickable, data filters correctly, summary updates
- Cashflows: void button labeled, red, visible in action row
- Payments: void button visible + readable
- Projects: no jenis badge displayed

**Impact:** User experience improved (Critiques #1, #4 resolved)

---

## Dependencies Between Commits

```
Commit 1 (Migrations)
  ↓
Commit 2 (Models) — requires M1 migrations run
  ↓
Commit 3 (Services) — requires M2 (jenis drop)
  ↓
Commit 4 (Validation + Imports/Exports) — requires models + jenis drop
  ↓
Commit 5 (Forms + Livewire) — requires models + validation
  ↓
Commit 6 (Views) — requires all above
```

**Non-blocking:** Commits 3 & 4 can run in parallel after commit 2.

---

## Testing Strategy

### Unit Tests

| Test | File | Purpose | Status |
|------|------|---------|--------|
| Cast receivable fields | tests/Unit/Receivable/CastTest.php | Verify jenis_pajak, discount_percent, pajak_amount cast | NEW |
| Cast payable fields | tests/Unit/Payable/CastTest.php | Verify discount_percent, pajak_amount cast | NEW |
| Project po_number validation | tests/Feature/ProjectStatusTransitionTest.php | Ensure po_number required on Done | UPDATE (existing test adjust) |
| Dashboard no jenis keys | tests/Feature/DashboardStatisticsServiceTest.php | Verify projects_barang/jasa removed | UPDATE |

### Feature Tests

| Test | File | Purpose | Status |
|------|------|---------|--------|
| ProjectImport no jenis col | tests/Feature/Imports/ProjectImportTest.php | Validate import works without 'type' column | UPDATE |
| ReceivableImport new cols | tests/Feature/Imports/ReceivableImportTest.php | Map jenis_pajak, discount_percent (optional) | UPDATE |
| ProjectExport no jenis | tests/Feature/Exports/ProjectExportTest.php | Verify export output columns correct | UPDATE |
| ReceivableExport new cols | tests/Feature/Exports/ReceivableExportTest.php | Verify export includes 3 new cols | UPDATE |
| Project form no jenis | tests/Feature/Projects/CreateProjectTest.php | Verify jenis field removed from form | UPDATE |
| Receivable form tax/discount | tests/Feature/Receivables/CreateReceivableTest.php | Test tax dropdown + discount input work | NEW |
| Payable form discount | tests/Feature/Payables/CreatePayableTest.php | Test discount input works | UPDATE |
| Void button labeled | tests/Feature/Cashflows/VoidButtonTest.php | UI test: button visible + labeled (visual inspection) | NEW |

### Manual Testing

| Scenario | Trigger | Expected |
|----------|---------|----------|
| Create project | Form load | No 'jenis' field, 'pajak' required, input 0-100% |
| Create receivable with tax | Set project + jenis_pajak=ppn, nominal=100k, discount=10% | pajax_amount = (100k \* 0.9) \* 0.11 = 9.9k (readonly display) |
| Create payable with discount | Set nominal=100k, discount=5%, jenis_pajak=ppn, pajak_include=true | pajax_amount calc correctly (readonly) |
| Void cash settlement | Cashflows index, settlement entry, staff click "Request Void" | Modal shows, button labeled + red, visible |
| AR tabs filter | Receivables index, click "Unbilled" tab | Table filters to unbilled entries, summary card updates |
| Import project no PO | ProjectImport template, submit without PO column | Import succeeds, po_number=null, status can be Done |

---

## Rollback Plan

### Step-by-Step Reversal

| Step | Action | Command | Notes |
|------|--------|---------|-------|
| 1 | Revert commit 6 (Views) | `git revert <sha-commit-6>` | No DB changes |
| 2 | Revert commit 5 (Forms) | `git revert <sha-commit-5>` | No DB changes |
| 3 | Revert commit 4 (Validation) | `git revert <sha-commit-4>` | No DB changes |
| 4 | Revert commit 3 (Services) | `git revert <sha-commit-3>` | No DB changes |
| 5 | Revert commit 2 (Models) | `git revert <sha-commit-2>` | No DB changes |
| 6 | Revert migrations | `php artisan migrate:rollback --step=2` | Drops added cols, restores jenis col |

### Quick Rollback (Full Stack)

```bash
# Revert all code commits
git revert <sha-commit-6>..<sha-commit-1>

# Rollback migrations (2 steps)
php artisan migrate:rollback --step=2

# Verify database state
php artisan tinker
# Check: receivables.jenis_pajak removed, projects.jenis restored
```

### Partial Rollback (Code Only)

If migrations already applied and code needs revert:
```bash
git revert <sha-commit-6>..<sha-commit-2>
# Models still match DB schema; forms updated but models fallback to defaults
```

---

## Known Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Existing projects lose jenis data | Tax defaults disappear | Migration drops column (backward compat), audit log available for recovery; users must set pajak manually going forward |
| Import without PO creates "unbilled" receivables | Might confuse if po_number added later | User documentation: PO can be added via Project revise; UI shows status clearly |
| pajax_amount computed but not stored | Data not persisted | By design (readonly calculated field); stored in DB for audit/export but never user-edited directly |
| Void button styling breaks on small screens | Mobile UX issue | Test responsive: h-10 px-3 should fit; fallback to condensed label if needed |

---

## Deployment Checklist

- [ ] Branch `feature/cash-non-project-ap-ar` clean, all commits squashed or sequential
- [ ] All 6 commits applied in order
- [ ] `php artisan migrate` runs without error (2 migrations applied)
- [ ] `php artisan test` passes (8+ new tests + 4 updated tests)
- [ ] Manual testing: all scenarios pass (see Testing Strategy)
- [ ] Views cached: `php artisan view:cache` succeeds
- [ ] Build succeeds: `npm run build` (if Tailwind config changed)
- [ ] Rollback tested: revert all commits + migrations, DB state restored
- [ ] Documentation updated: README changelog, PROGRESS.md, this file
- [ ] PR merged to `main` (after review)

---

## Reference Links

- **Critique feedback:** User session 2026-10-11, critique alignment approved
- **Dependency map:** Section "Dependencies Between Commits" above
- **Status enum:** `app/Enums/ProjectStatus.php` (state machine validation)
- **Tax calculation:** `app/Livewire/Receivables/Create.php::pajaxAmount()` (computed property)
- **Import templates:** `app/Exports/ProjectTemplateExport.php` (used by ProjectImport)
- **Dashboard service:** `app/Services/DashboardStatisticsService.php` (projects_barang/jasa removed)

---

**Document Version:** 1.0 | **Updated:** 2026-10-11 | **Status:** APPROVED FOR IMPLEMENTATION
