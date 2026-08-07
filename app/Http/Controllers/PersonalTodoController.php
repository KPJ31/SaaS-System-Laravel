<?php

namespace App\Http\Controllers;

use App\Models\PersonalTodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonalTodoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeFilters($request);

        $todos = PersonalTodo::where('company_id', $this->companyId())
            ->where('user_id', auth()->id())
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->due === 'today', fn ($query) => $query->whereDate('due_date', today()))
            ->when($request->due === 'overdue', fn ($query) => $query->whereDate('due_date', '<', today())->where('status', 'open'))
            ->orderByDesc('pinned')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $baseQuery = PersonalTodo::where('company_id', $this->companyId())->where('user_id', auth()->id());

        return view('personal-todos.index', [
            'todos' => $todos,
            'summary' => [
                'open' => (clone $baseQuery)->where('status', 'open')->count(),
                'overdue' => (clone $baseQuery)->where('status', 'open')->whereDate('due_date', '<', today())->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'pinned' => (clone $baseQuery)->where('pinned', true)->count(),
            ],
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['pinned'] = $request->boolean('pinned');

        PersonalTodo::create($data + [
            'company_id' => $this->companyId(),
            'user_id' => auth()->id(),
            'status' => 'open',
        ]);

        return back()->with('success', 'Todo created.');
    }

    public function update(Request $request, PersonalTodo $todo): RedirectResponse
    {
        $this->abortUnlessOwnTodo($todo);
        $data = $this->validated($request);
        $data['status'] = $request->input('status', $todo->status);
        $data['completed_at'] = $data['status'] === 'completed' ? ($todo->completed_at ?? now()) : null;
        $data['pinned'] = $request->boolean('pinned');
        $todo->update($data);

        return back()->with('success', 'Todo updated.');
    }

    public function complete(PersonalTodo $todo): RedirectResponse
    {
        $this->abortUnlessOwnTodo($todo);
        $todo->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'Todo completed.');
    }

    public function destroy(PersonalTodo $todo): RedirectResponse
    {
        $this->abortUnlessOwnTodo($todo);
        $todo->update(['status' => 'dismissed']);

        return back()->with('success', 'Todo dismissed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['nullable', Rule::in(['open', 'completed'])],
            'due_date' => ['nullable', 'date'],
            'pinned' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeFilters(Request $request): void
    {
        abort_unless(! $request->filled('status') || in_array($request->status, ['open', 'completed', 'dismissed'], true), 404);
        abort_unless(! $request->filled('priority') || in_array($request->priority, ['low', 'medium', 'high', 'urgent'], true), 404);
        abort_unless(! $request->filled('due') || in_array($request->due, ['today', 'overdue'], true), 404);
    }

    private function abortUnlessOwnTodo(PersonalTodo $todo): void
    {
        abort_unless((int) $todo->company_id === $this->companyId() && (int) $todo->user_id === (int) auth()->id(), 403);
    }

    private function companyId(): int
    {
        return (int) auth()->user()->company_id;
    }

    private function routePrefix(): string
    {
        return auth()->user()->role === 'company_admin' ? 'company-admin.todos' : 'employee.todos';
    }
}
