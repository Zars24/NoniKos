<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Property;
use App\Services\ActivityLogger;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = Expense::with(['category:id,name', 'property:id,name'])
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('spent_on', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('spent_on', '<=', $to))
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('expense_category_id', $id))
            ->latest('spent_on')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'total' => Expense::when($request->query('from'), fn ($q, $from) => $q->whereDate('spent_on', '>=', $from))
                ->when($request->query('to'), fn ($q, $to) => $q->whereDate('spent_on', '<=', $to))
                ->sum('amount'),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Expense::class);

        return view('expenses.create', [
            'expense' => new Expense(['spent_on' => today()]),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Expense::class);

        $expense = Expense::create($this->validated($request));

        app(ActivityLogger::class)->log('expense.created', $expense, 'Pengeluaran dicatat: '.$expense->description, ['amount' => $expense->amount]);

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran dicatat.');
    }

    public function edit(Expense $expense)
    {
        $this->authorize('update', $expense);

        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        app(ActivityLogger::class)->log('expense.deleted', $expense, 'Pengeluaran dihapus: '.$expense->description, ['amount' => $expense->amount]);

        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran dihapus.');
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', Expense::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('expense_categories')->where('organization_id', $request->user()->activeMembership()->organization_id)],
        ]);

        ExpenseCategory::create($data);

        return back()->with('status', 'Kategori ditambahkan.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'spent_on' => ['required', 'date'],
            'amount' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:200'],
            'expense_category_id' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'property_id' => ['nullable', 'integer', Rule::exists('properties', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
        ]);

        $data['amount'] = Money::parse($data['amount']);
        $data['recorded_by'] = $request->user()->id;

        return $data;
    }
}