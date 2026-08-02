<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\CompanyAdmin\Concerns\HandlesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    use HandlesCompanyAccess;

    public function index(Request $request): View
    {
        $feedback = Feedback::with(['client', 'project'])
            ->where('company_id', $this->companyId())
            ->when($request->rating, fn ($query, $rating) => $query->where('rating', $rating))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('company-admin.feedback.index', [
            'feedback' => $feedback,
            'averageRating' => Feedback::where('company_id', $this->companyId())->avg('rating'),
            'feedbackCount' => Feedback::where('company_id', $this->companyId())->count(),
        ]);
    }

    public function updateStatus(Feedback $feedback, string $status): RedirectResponse
    {
        $this->abortUnlessCompanyRecord($feedback);
        abort_unless(in_array($status, ['pending', 'approved', 'hidden'], true), 404);

        $feedback->update(['status' => $status, 'approved_at' => $status === 'approved' ? now() : null]);

        return back()->with('success', 'Feedback status updated.');
    }
}
