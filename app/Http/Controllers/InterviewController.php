<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InterviewAnswer;
use App\Models\InterviewQuestion;
use App\Models\InterviewSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:120',
            'status' => 'nullable|in:pending,answered,needs_review,approved,rejected,blocked',
            'impact' => 'nullable|in:low,medium,high,critical',
        ]);

        $applyFilters = function (Builder $query) use ($filters): void {
            $query
                ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
                ->when($filters['impact'] ?? null, fn (Builder $q, string $impact) => $q->where('impact', $impact))
                ->when($filters['q'] ?? null, function (Builder $q, string $search): void {
                    $q->where(function (Builder $nested) use ($search): void {
                        $nested->where('question', 'like', "%{$search}%")
                            ->orWhere('explanation', 'like', "%{$search}%")
                            ->orWhere('why_it_matters', 'like', "%{$search}%");
                    });
                });
        };

        $sections = InterviewSection::query()
            ->whereHas('questions', $applyFilters)
            ->with(['questions' => function (Builder $query) use ($applyFilters): void {
                $applyFilters($query);
                $query->with(['answer', 'answers'])->orderBy('position');
            }])
            ->orderBy('position')
            ->get();

        $statusCounts = InterviewQuestion::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.interview', [
            'sections' => $sections,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'totalQuestions' => InterviewQuestion::count(),
            'visibleQuestions' => $sections->sum(fn ($section) => $section->questions->count()),
        ]);
    }

    public function save(Request $request, InterviewQuestion $question)
    {
        $data = $request->validate([
            'answer' => 'nullable|string|max:10000',
            'comment' => 'nullable|string|max:5000',
            'status' => 'required|in:pending,answered,needs_review,approved,rejected,blocked',
        ]);

        InterviewAnswer::create($data + [
            'interview_question_id' => $question->id,
            'user_id' => Auth::id(),
            'answered_at' => now(),
        ]);
        $question->update(['status' => $data['status']]);
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'interview.answer.saved',
            'auditable_type' => InterviewQuestion::class,
            'auditable_id' => $question->id,
        ]);

        return back()->with('ok', 'Respuesta guardada con historial.');
    }

    public function report(Request $request)
    {
        $questions = InterviewQuestion::with('section', 'answer')->get();
        if ($request->query('format') === 'json') return response()->json($questions);
        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($questions): void {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['categoria', 'pregunta', 'estado', 'impacto', 'respuesta']);
                foreach ($questions as $question) {
                    fputcsv($file, [$question->section->name, $question->question, $question->status, $question->impact, $question->answer?->answer]);
                }
                fclose($file);
            }, 'entrevista.csv');
        }
        if ($request->query('format') === 'md') {
            return response(view('admin.report-markdown', compact('questions')))
                ->header('Content-Type', 'text/markdown')
                ->header('Content-Disposition', 'attachment; filename=entrevista.md');
        }

        return view('admin.report', compact('questions'));
    }
}
