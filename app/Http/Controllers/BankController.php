<?php

namespace App\Http\Controllers;

use App\Exports\BankQuestionsExport;
use App\Imports\BankQuestionsImport;
use App\Models\Bank;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BankController extends Controller
{
    private function generateBankRef(): string
    {
        $nextNumber = Bank::withTrashed()->count() + 1;

        do {
            $ref = 'BANK-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $exists = Bank::withTrashed()
                ->where('ref', $ref)
                ->exists();

            $nextNumber++;
        } while ($exists);

        return $ref;
    }

    private function answerKeyFromQuestion(Question $question): string
    {
        if ($question->correct_answer === $question->response2) {
            return 'response2';
        }

        if ($question->correct_answer === $question->response3) {
            return 'response3';
        }

        return 'response1';
    }

    private function ensureQuestionBelongsToBank(Question $question, Bank $bank): void
    {
        abort_if((int) $question->bank_id !== (int) $bank->id, 404);
    }

    private function mapCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'hashid' => $course->hashid ?? null,
            'title' => $course->title,
            'image' => $course->image ?? null,
            'image_url' => $course->image_url ?? null,
            'category' => $course->category,
        ];
    }

    private function mapQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'bank_id' => $question->bank_id,
            'name_chapter' => $question->name_chapter,
            'question' => $question->question,
            'response1' => $question->response1,
            'response2' => $question->response2,
            'response3' => $question->response3,
            'correct_answer' => $question->correct_answer,
            'correct_answer_key' => $this->answerKeyFromQuestion($question),
            'is_modified' => (bool) $question->is_modified,
            'created_at' => $question->created_at,
        ];
    }

    private function mapBank(Bank $bank): array
    {
        return [
            'id' => $bank->id,
            'course_id' => $bank->course_id,
            'ref' => $bank->ref,
            'title' => $bank->title,
            'description' => $bank->description,
            'questions_count' => $bank->questions_count ?? $bank->questions()->count(),
            'created_at' => $bank->created_at,
            'course' => $bank->course ? $this->mapCourse($bank->course) : null,
        ];
    }

    public function index()
    {
        $banks = Bank::with(['course.category'])
            ->withCount('questions')
            ->latest()
            ->get()
            ->map(fn ($bank) => $this->mapBank($bank))
            ->values();

        $courses = Course::with('category')
            ->orderBy('title')
            ->get()
            ->map(fn ($course) => $this->mapCourse($course))
            ->values();

        return Inertia::render('Banks/IndexBank', [
            'banks' => $banks,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'ref' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('banks', 'ref')->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            Bank::create([
                'course_id' => $validated['course_id'],
                'ref' => $validated['ref'] ?: $this->generateBankRef(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route('banks.index')
                ->with('success', 'Bank has been created successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while creating the bank.',
                ])
                ->withInput();
        }
    }

    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'ref' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('banks', 'ref')
                    ->ignore($bank->id)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $bank->update([
                'course_id' => $validated['course_id'],
                'ref' => $validated['ref'] ?: $bank->ref,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route('banks.index')
                ->with('success', 'Bank has been updated successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while updating the bank.',
                ])
                ->withInput();
        }
    }

    public function destroy(Bank $bank)
    {
        try {
            DB::beginTransaction();

            $bank->questions()->delete();
            $bank->delete();

            DB::commit();

            return redirect()
                ->route('banks.index')
                ->with('success', 'Bank has been deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while deleting the bank.',
                ]);
        }
    }

    public function questions(Bank $bank)
    {
        $bank->load([
            'course.category',
            'questions' => function ($query) {
                $query->latest();
            },
        ]);

        return Inertia::render('Banks/Questions', [
            'bank' => [
                'id' => $bank->id,
                'course_id' => $bank->course_id,
                'ref' => $bank->ref,
                'title' => $bank->title,
                'description' => $bank->description,
                'course' => $bank->course ? $this->mapCourse($bank->course) : null,
                'questions' => $bank->questions
                    ->map(fn ($question) => $this->mapQuestion($question))
                    ->values(),
            ],
        ]);
    }

    public function storeQuestion(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'name_chapter' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string'],
            'response1' => ['required', 'string'],
            'response2' => ['required', 'string'],
            'response3' => ['required', 'string'],
            'correct_answer' => ['required', 'string', 'in:response1,response2,response3'],
        ]);

        try {
            Question::create([
                'bank_id' => $bank->id,
                'name_chapter' => $validated['name_chapter'] ?? null,
                'question' => $validated['question'],
                'response1' => $validated['response1'],
                'response2' => $validated['response2'],
                'response3' => $validated['response3'],
                'correct_answer' => $validated[$validated['correct_answer']],
                'is_modified' => false,
            ]);

            return redirect()
                ->route('banks.questions', $bank->id)
                ->with('success', 'Question has been created successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while creating the question.',
                ])
                ->withInput();
        }
    }

    public function updateQuestion(Request $request, Bank $bank, Question $question)
    {
        $this->ensureQuestionBelongsToBank($question, $bank);

        $validated = $request->validate([
            'name_chapter' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string'],
            'response1' => ['required', 'string'],
            'response2' => ['required', 'string'],
            'response3' => ['required', 'string'],
            'correct_answer' => ['required', 'string', 'in:response1,response2,response3'],
        ]);

        try {
            $question->update([
                'name_chapter' => $validated['name_chapter'] ?? null,
                'question' => $validated['question'],
                'response1' => $validated['response1'],
                'response2' => $validated['response2'],
                'response3' => $validated['response3'],
                'correct_answer' => $validated[$validated['correct_answer']],
                'is_modified' => true,
            ]);

            return redirect()
                ->route('banks.questions', $bank->id)
                ->with('success', 'Question has been updated successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while updating the question.',
                ])
                ->withInput();
        }
    }

    public function destroyQuestion(Bank $bank, Question $question)
    {
        $this->ensureQuestionBelongsToBank($question, $bank);

        try {
            $question->delete();

            return redirect()
                ->route('banks.questions', $bank->id)
                ->with('success', 'Question has been deleted successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while deleting the question.',
                ]);
        }
    }

    public function exportQuestions(Bank $bank)
    {
        $fileName = 'questions-' . $bank->ref . '.xlsx';

        return Excel::download(new BankQuestionsExport($bank), $fileName);
    }

    public function importQuestions(Request $request, Bank $bank)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            Excel::import(new BankQuestionsImport($bank), $request->file('file'));

            return redirect()
                ->route('banks.questions', $bank->id)
                ->with('success', 'Questions imported successfully.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'file' => 'Unable to import questions. Please check the file format.',
                ]);
        }
    }
}