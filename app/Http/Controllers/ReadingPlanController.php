<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(IndexReadingPlanRequest $request): View
    {
        $validated = $request->validated();
        $currentStatus = $validated['status'] ?? null;

        $query = $request->user()
            ->readingPlans()
            ->with('book');

        if ($currentStatus !== null) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    public function create(): View
    {
        $books = Book::query()
            ->orderBy('title')
            ->get();

        return View('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()
            ->readingPlans()
            ->create([
                'book_id' => $validated['book_id'],
                'target_date' => $validated['target_date'],
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を登録しました。');
    }

    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $plan->load('book');

        return view('reading-plans.edit',
            ['readingPlan' => $plan]);
    }

    public function update(UpdateReadingPlanRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        $plan->update([
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読了しました。');
    }
}
