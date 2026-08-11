<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\NonProjectExpense;
use Illuminate\Pagination\LengthAwarePaginator;

class NonProjectExpenseService
{
    /**
     * Create a new non-project expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NonProjectExpense
    {
        return NonProjectExpense::create($data + ['created_by' => auth()->id()]);
    }

    /**
     * Update an existing non-project expense.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(NonProjectExpense $expense, array $data): NonProjectExpense
    {
        $expense->update($data);

        return $expense->refresh();
    }

    /**
     * Delete a non-project expense.
     */
    public function delete(NonProjectExpense $expense): void
    {
        $expense->delete();
    }

    /**
     * List non-project expenses, optionally filtered by akun and date range.
     */
    public function paginate(?Akun $akun = null, ?string $startDate = null, ?string $endDate = null, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return NonProjectExpense::query()
            ->with(['akun', 'vendor', 'supplier', 'mandor', 'investor'])
            ->when($akun, fn ($query) => $query->where('akun_id', $akun->id))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->when($search !== '', fn ($query) => $query->where('keterangan', 'like', '%'.$search.'%'))
            ->latest('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }
}
