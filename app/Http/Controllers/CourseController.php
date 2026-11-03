<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Plan;
use App\Models\PlanDetail;
use App\Models\Slide;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

use App\Services\VoiceOver\VoiceOverService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    private const COURSE_METHODS = [
        'Online e-learning (LMS)',
        'Virtual classroom',
        'On-site classroom training',
        'Blended learning',
        'Self-study',
    ];

    public function index()
    {
        $courses = Course::with(['category', 'tags'])
            ->withCount(['chapters', 'slides'])
            ->where('content_type', 'normal')
            ->latest()
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'hashid' => $course->hashid,
                    'category_id' => $course->category_id,
                    'ref' => $course->ref,
                    'title' => $course->title,
                    'description' => $course->description,
                    'type' => $course->type,
                    'training_type' => $course->training_type,
                    'method' => $course->method,
                    'duration_hours' => (float) ($course->duration_hours ?? 0),
                    'passing_score' => (float) ($course->passing_score ?? 0),
                    'syllabus' => $course->syllabus,
                    'syllabus_url' => $course->syllabus
                        ? asset('storage/' . $course->syllabus)
                        : null,
                    'skill_level' => $course->skill_level,
                    'course_focus' => $course->course_focus,
                    'language' => $course->language,
                    'price' => $course->price,
                    'currency' => $course->currency,
                    'is_published' => (bool) $course->is_published,

                    'image' => $course->image,
                    'image_url' => $course->image ? asset('storage/' . $course->image) : null,
                    'support_voice_over' => (bool) $course->support_voice_over,

                    'chapters_count' => $course->chapters_count,
                    'slides_count' => $course->slides_count,
                    'created_at' => $course->created_at?->format('Y-m-d'),

                    'category' => $course->category ? [
                        'id' => $course->category->id,
                        'name' => $course->category->name,
                    ] : null,

                    'tags' => $course->tags->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                        ];
                    })->values(),
                ];
            });

        return inertia('Courses/IndexCourses', [
            'courses' => $courses,
        ]);
    }

    public function create()
    {
        return Inertia::render('Courses/Create', [
            'categories' => Category::where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'tags' => Tag::select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ref' => ['required', 'string', 'max:100', 'unique:courses,ref'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', 'string', 'max:100'],
            'training_type' => ['required', 'string', 'max:100'],
            'method' => [
                'required',
                'string',
                Rule::in(self::COURSE_METHODS),
            ],
            'duration_hours' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'skill_level' => ['nullable', 'string', 'max:100'],
            'course_focus' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:20'],
            'is_published' => ['required', 'boolean'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'syllabus' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            'support_voice_over' => ['nullable', 'boolean'],
            'voice_over_auto_play' => ['nullable', 'boolean'],
            'voice_over_provider' => ['nullable', 'string', 'max:100'],
            'voice_over_lang' => ['nullable', 'string', 'max:20'],
            'voice_over_voice' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $imagePath = null;
        $syllabusPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('courses/images', 'public');
            }

            if ($request->hasFile('syllabus')) {
                $syllabusPath = $request->file('syllabus')->store('courses/syllabuses', 'public');
            }

            $supportVoiceOver = $request->boolean('support_voice_over');

            $course = Course::create([
                'category_id' => $validated['category_id'],
                'ref' => trim($validated['ref']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'training_type' => $validated['training_type'],
                'method' => $validated['method'],
                'duration_hours' => $validated['duration_hours'],
                'passing_score' => $validated['passing_score'],
                'syllabus' => $syllabusPath,
                'skill_level' => $validated['skill_level'] ?? null,
                'course_focus' => $validated['course_focus'] ?? null,
                'language' => $validated['language'] ?? 'English',
                'price' => $validated['price'] ?? 0,
                'currency' => $validated['currency'] ?? 'MAD',
                'is_published' => $request->boolean('is_published'),
                'image' => $imagePath,

                'support_voice_over' => $supportVoiceOver,
                'voice_over_auto_play' => $supportVoiceOver
                    ? $request->boolean('voice_over_auto_play')
                    : false,
                'voice_over_provider' => $supportVoiceOver
                    ? ($validated['voice_over_provider'] ?? 'voicerss')
                    : 'voicerss',
                'voice_over_lang' => $supportVoiceOver
                    ? ($validated['voice_over_lang'] ?? 'en-us')
                    : 'en-us',
                'voice_over_voice' => $supportVoiceOver
                    ? ($validated['voice_over_voice'] ?? 'default')
                    : 'default',
            ]);

            $course->tags()->sync($validated['tags'] ?? []);

            DB::commit();

            return redirect()
                ->route('courses.edit', $course->hashid)
                ->with(
                    'success',
                    'Course has been created successfully. You can now add chapters, slides and plan.'
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            if ($syllabusPath && Storage::disk('public')->exists($syllabusPath)) {
                Storage::disk('public')->delete($syllabusPath);
            }

            report($e);

            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while creating the course.'),
                ])
                ->withInput();
        }
    }

    public function edit(Request $request, $hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('Invalid course ID.'),
                ]);
        }

        $course = Course::findOrFail($id);

        $course->load([
            'category',
            'tags',

            'chapters' => function ($query) {
                $query->orderBy('sort_order');
            },

            'chapters.slides' => function ($query) {
                $query->orderBy('sort_order');
            },

            'chapters.slides.questions' => function ($query) {
                $query->orderBy('sort_order');
            },

            'plan.details.children',
        ]);

        return inertia('Courses/Edit', [
            'step' => (int) $request->query('step', 1),

            'categories' => Category::where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'tags' => Tag::select('id', 'name')
                ->orderBy('name')
                ->get(),

            'course' => [
                'id' => $course->id,
                'hashid' => $course->hashid,
                'category_id' => $course->category_id,
                'ref' => $course->ref,
                'title' => $course->title,
                'description' => $course->description,
                'type' => $course->type,
                'training_type' => $course->training_type,
                'method' => $course->method,
                'duration_hours' => (float) ($course->duration_hours ?? 0),
                'passing_score' => (float) ($course->passing_score ?? 0),
                'syllabus' => $course->syllabus,
                'syllabus_url' => $course->syllabus
                    ? asset('storage/' . $course->syllabus)
                    : null,
                'syllabus_name' => $course->syllabus
                    ? basename($course->syllabus)
                    : null,
                'skill_level' => $course->skill_level,
                'course_focus' => $course->course_focus,
                'language' => $course->language,
                'price' => $course->price,
                'currency' => $course->currency,
                'is_published' => (bool) $course->is_published,

                'image' => $course->image,
                'image_url' => $course->image
                    ? asset('storage/' . $course->image)
                    : null,

                'support_voice_over' => (bool) $course->support_voice_over,
                'voice_over_auto_play' => (bool) $course->voice_over_auto_play,
                'voice_over_provider' => $course->voice_over_provider ?: 'voicerss',
                'voice_over_lang' => $course->voice_over_lang ?: 'en-us',
                'voice_over_voice' => $course->voice_over_voice ?: 'default',

                'tags' => $course->tags
                    ->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                        ];
                    })
                    ->values(),

                'chapters' => $course->chapters
                    ->map(function ($chapter) {
                        return [
                            'id' => $chapter->id,
                            'title' => $chapter->title,
                            'description' => $chapter->description,
                            'sort_order' => $chapter->sort_order,

                            'slides' => $chapter->slides
                                ->map(function ($slide) {
                                    return [
                                        'id' => $slide->id,
                                        'chapter_id' => $slide->chapter_id,
                                        'title' => $slide->title,
                                        'lesson_type' => $slide->lesson_type,
                                        'content' => $slide->content,

                                        'video_url' => $slide->video_url,
                                        'video_duration' => $slide->video_duration,

                                        'file' => null,
                                        'file_path' => $slide->file_path,
                                        'file_name' => $slide->file_name,
                                        'file_type' => $slide->file_type,
                                        'file_size' => $slide->file_size,

                                        'external_url' => $slide->external_url,

                                        'is_preview' => (bool) $slide->is_preview,
                                        'is_mandatory' => (bool) $slide->is_mandatory,
                                        'is_downloadable' => (bool) $slide->is_downloadable,
                                        'is_active' => (bool) $slide->is_active,

                                        'voice_over_enabled' => (bool) $slide->voice_over_enabled,
                                        'voice_over_text' => $slide->voice_over_text,

                                        'voice_over_audio_path' => $slide->voice_over_audio_path,

                                        'voice_over_audio_url' => $slide->voice_over_audio_path
                                            ? asset('storage/' . $slide->voice_over_audio_path)
                                            : null,

                                        'voice_over_audio_name' => $slide->voice_over_audio_name,
                                        'voice_over_audio_type' => $slide->voice_over_audio_type,
                                        'voice_over_audio_size' => $slide->voice_over_audio_size,
                                        'voice_over_provider' => $slide->voice_over_provider,
                                        'voice_over_voice' => $slide->voice_over_voice,
                                        'voice_over_lang' => $slide->voice_over_lang,
                                        'voice_over_auto_play' => (bool) $slide->voice_over_auto_play,

                                        'sort_order' => $slide->sort_order,

                                        'questions' => $slide->questions
                                            ->map(function ($question) {
                                                return [
                                                    'id' => $question->id,
                                                    'question_type' => $question->question_type ?: 'qcm',
                                                    'question' => $question->question,
                                                    'option_a' => $question->option_a,
                                                    'option_b' => $question->option_b,
                                                    'option_c' => $question->option_c,
                                                    'correct_answer' => $question->correct_answer,
                                                    'explanation' => $question->explanation,
                                                    'sort_order' => $question->sort_order,
                                                ];
                                            })
                                            ->values(),
                                    ];
                                })
                                ->values(),
                        ];
                    })
                    ->values(),

                'plan' => [
                    'id' => $course->plan?->id,
                    'name' => $course->plan?->name ?? '',
                    'is_active' => $course->plan?->is_active ?? true,

                    'details' => $course->plan
                        ? $this->buildPlanDetailsTree($course->plan->details)
                        : [],
                ],
            ],
        ]);
    }

    public function destroy(Request $request, $hashid)
    {
        try {
            DB::beginTransaction();

            $id = Hashids::decode($hashid)[0] ?? abort(404);
            $course = Course::findOrFail($id);
            $course->delete();

            DB::commit();

            return back()->with([
                'success' => 'Course deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => __('An error occurred while deleting the course.'),
            ]);
        }
    }


    public function updateBasicInfo(Request $request, Course $course)
    {
        $validated = $request->validate([
            'ref' => [
                'required',
                'string',
                'max:100',
                Rule::unique('courses', 'ref')->ignore($course->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', 'string', 'max:100'],
            'training_type' => ['required', 'string', 'max:100'],
            'method' => [
                'required',
                'string',
                Rule::in(self::COURSE_METHODS),
            ],
            'duration_hours' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'skill_level' => ['nullable', 'string', 'max:100'],
            'course_focus' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:20'],
            'is_published' => ['required', 'boolean'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'syllabus' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_syllabus' => ['nullable', 'boolean'],

            'support_voice_over' => ['nullable', 'boolean'],
            'voice_over_auto_play' => ['nullable', 'boolean'],
            'voice_over_provider' => ['nullable', 'string', 'max:100'],
            'voice_over_lang' => ['nullable', 'string', 'max:20'],
            'voice_over_voice' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $imagePath = $course->image;
        $syllabusPath = $course->syllabus;

        $newImagePath = null;
        $newSyllabusPath = null;
        $oldImagePath = null;
        $oldSyllabusPath = null;

        try {
            DB::beginTransaction();

            if ($request->boolean('remove_image') && $course->image) {
                $oldImagePath = $course->image;
                $imagePath = null;
            }

            if ($request->hasFile('image')) {
                $oldImagePath = $course->image;
                $newImagePath = $request->file('image')->store('courses/images', 'public');
                $imagePath = $newImagePath;
            }

            if ($request->boolean('remove_syllabus') && $course->syllabus) {
                $oldSyllabusPath = $course->syllabus;
                $syllabusPath = null;
            }

            if ($request->hasFile('syllabus')) {
                $oldSyllabusPath = $course->syllabus;
                $newSyllabusPath = $request
                    ->file('syllabus')
                    ->store('courses/syllabuses', 'public');
                $syllabusPath = $newSyllabusPath;
            }

            $supportVoiceOver = $request->boolean('support_voice_over');

            $course->update([
                'category_id' => $validated['category_id'],
                'ref' => trim($validated['ref']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'training_type' => $validated['training_type'],
                'method' => $validated['method'],
                'duration_hours' => $validated['duration_hours'],
                'passing_score' => $validated['passing_score'],
                'syllabus' => $syllabusPath,
                'skill_level' => $validated['skill_level'] ?? null,
                'course_focus' => $validated['course_focus'] ?? null,
                'language' => $validated['language'] ?? 'English',
                'price' => $validated['price'] ?? 0,
                'currency' => $validated['currency'] ?? 'MAD',
                'is_published' => $request->boolean('is_published'),
                'image' => $imagePath,

                'support_voice_over' => $supportVoiceOver,
                'voice_over_auto_play' => $supportVoiceOver
                    ? $request->boolean('voice_over_auto_play')
                    : false,
                'voice_over_provider' => $supportVoiceOver
                    ? ($validated['voice_over_provider'] ?? 'voicerss')
                    : 'voicerss',
                'voice_over_lang' => $supportVoiceOver
                    ? ($validated['voice_over_lang'] ?? 'en-us')
                    : 'en-us',
                'voice_over_voice' => $supportVoiceOver
                    ? ($validated['voice_over_voice'] ?? 'default')
                    : 'default',
            ]);

            $course->tags()->sync($validated['tags'] ?? []);

            DB::commit();

            if (
                $oldImagePath &&
                $oldImagePath !== $imagePath &&
                Storage::disk('public')->exists($oldImagePath)
            ) {
                Storage::disk('public')->delete($oldImagePath);
            }

            if (
                $oldSyllabusPath &&
                $oldSyllabusPath !== $syllabusPath &&
                Storage::disk('public')->exists($oldSyllabusPath)
            ) {
                Storage::disk('public')->delete($oldSyllabusPath);
            }

            return redirect()
                ->route('courses.edit', [
                    'course' => $course->hashid,
                    'step' => 2,
                ])
                ->with('success', __('Course information has been updated successfully.'));
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($newImagePath && Storage::disk('public')->exists($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            if ($newSyllabusPath && Storage::disk('public')->exists($newSyllabusPath)) {
                Storage::disk('public')->delete($newSyllabusPath);
            }

            report($e);

            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while updating the course.'),
                ])
                ->withInput();
        }
    }

    public function storeChapter(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $sortOrder = ((int) $course->chapters()->max('sort_order')) + 1;

            Chapter::create([
                'course_id' => $course->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $sortOrder,
            ]);

            return redirect()
                ->route('courses.edit', ['course' => $course->hashid, 'step' => 2])
                ->with('success', __('Chapter has been created successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while creating the chapter.'),
                ]);
        }
    }

    public function updateChapter(Request $request, Course $course, Chapter $chapter)
    {
        abort_if($chapter->course_id !== $course->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $chapter->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route('courses.edit', ['course' => $course->hashid, 'step' => 2])
                ->with('success', __('Chapter has been updated successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while updating the chapter.'),
                ]);
        }
    }

    public function destroyChapter(Course $course, Chapter $chapter)
    {
        abort_if($chapter->course_id !== $course->id, 404);

        try {
            $chapter->delete();

            $course->chapters()
                ->orderBy('sort_order')
                ->get()
                ->values()
                ->each(function ($chapter, $index) {
                    $chapter->update([
                        'sort_order' => $index + 1,
                    ]);
                });
            return redirect()
                ->route('courses.edit', ['course' => $course->hashid, 'step' => 2])
                ->with('success', __('Chapter has been deleted successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while deleting the chapter.'),
                ]);
        }
    }

    public function reorderChapters(Request $request, Course $course)
    {
        $validated = $request->validate([
            'chapters' => ['required', 'array'],
            'chapters.*.id' => ['required', 'exists:chapters,id'],
            'chapters.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($course, $validated) {
                foreach ($validated['chapters'] as $chapterData) {
                    Chapter::where('id', $chapterData['id'])
                        ->where('course_id', $course->id)
                        ->update([
                            'sort_order' => $chapterData['sort_order'],
                        ]);
                }
            });

            return redirect()
                ->route('courses.edit', ['course' => $course->hashid, 'step' => 2])
                ->with('success', __('Chapters order has been updated successfully.'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while reordering chapters.'),
                ]);
        }
    }

    private function getSlideFileData(array $slideData): array
    {
        $filePath = $slideData['file_path'] ?? null;
        $fileName = $slideData['file_name'] ?? null;
        $fileType = $slideData['file_type'] ?? null;
        $fileSize = $slideData['file_size'] ?? null;

        if (
            isset($slideData['file']) &&
            $slideData['file'] instanceof \Illuminate\Http\UploadedFile
        ) {
            $file = $slideData['file'];

            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            $filePath = $file->store('courses/slides', 'public');
        }

        return [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
        ];
    }

    public function storeSlide(Request $request, Course $course, Chapter $chapter)
    {
        abort_if((int) $chapter->course_id !== (int) $course->id, 404);

        $validated = $this->validateSlideRequest($request);

        DB::transaction(function () use ($request, $validated, $chapter) {
            $nextSortOrder = ((int) $chapter->slides()->max('sort_order')) + 1;

            $fileData = $this->handleSlideFileUpload($request);

            $slide = $chapter->slides()->create([
                'title' => $validated['title'],
                'lesson_type' => $validated['lesson_type'],

                'content' => $validated['lesson_type'] === 'text'
                    ? ($validated['content'] ?? null)
                    : null,

                'video_url' => $validated['lesson_type'] === 'video'
                    ? ($validated['video_url'] ?? null)
                    : null,

                'video_duration' => $validated['lesson_type'] === 'video'
                    ? ($validated['video_duration'] ?? null)
                    : null,

                'file_path' => $validated['lesson_type'] === 'file'
                    ? ($fileData['file_path'] ?? null)
                    : null,

                'file_name' => $validated['lesson_type'] === 'file'
                    ? ($fileData['file_name'] ?? null)
                    : null,

                'file_type' => $validated['lesson_type'] === 'file'
                    ? ($fileData['file_type'] ?? null)
                    : null,

                'file_size' => $validated['lesson_type'] === 'file'
                    ? ($fileData['file_size'] ?? null)
                    : null,

                'external_url' => $validated['lesson_type'] === 'link'
                    ? ($validated['external_url'] ?? null)
                    : null,

                'is_preview' => $request->boolean('is_preview'),
                'is_mandatory' => $request->boolean('is_mandatory', true),
                'is_downloadable' => $request->boolean('is_downloadable'),
                'is_active' => $request->boolean('is_active', true),

                'voice_over_enabled' => $request->boolean('voice_over_enabled'),
                'voice_over_text' => $validated['voice_over_text'] ?? null,

                'sort_order' => $nextSortOrder,
            ]);

            $this->syncSlideQuestions($slide, $request);
        });

        return $this->redirectToSlidesStep($course, 'Slide has been created successfully.');
    }

    public function updateSlide(Request $request, Course $course, Slide $slide)
    {
        abort_if((int) $slide->chapter?->course_id !== (int) $course->id, 404);

        $validated = $this->validateSlideRequest($request);

        DB::transaction(function () use ($request, $validated, $slide) {
            $fileData = [];

            if ($request->hasFile('file')) {
                $this->deleteSlideFile($slide);
                $fileData = $this->handleSlideFileUpload($request);
            }

            $data = [
                'title' => $validated['title'],
                'lesson_type' => $validated['lesson_type'],

                'content' => $validated['lesson_type'] === 'text'
                    ? ($validated['content'] ?? null)
                    : null,

                'video_url' => $validated['lesson_type'] === 'video'
                    ? ($validated['video_url'] ?? null)
                    : null,

                'video_duration' => $validated['lesson_type'] === 'video'
                    ? ($validated['video_duration'] ?? null)
                    : null,

                'external_url' => $validated['lesson_type'] === 'link'
                    ? ($validated['external_url'] ?? null)
                    : null,

                'is_preview' => $request->boolean('is_preview'),
                'is_mandatory' => $request->boolean('is_mandatory', true),
                'is_downloadable' => $request->boolean('is_downloadable'),
                'is_active' => $request->boolean('is_active', true),

                'voice_over_enabled' => $request->boolean('voice_over_enabled'),
                'voice_over_text' => $validated['voice_over_text'] ?? null,
            ];

            if ($validated['lesson_type'] === 'file') {
                if (!empty($fileData)) {
                    $data = array_merge($data, $fileData);
                }
            } else {
                $this->deleteSlideFile($slide);

                $data['file_path'] = null;
                $data['file_name'] = null;
                $data['file_type'] = null;
                $data['file_size'] = null;
            }

            $slide->update($data);

            $this->syncSlideQuestions($slide, $request);
        });

        return $this->redirectToSlidesStep($course, 'Slide has been updated successfully.');
    }

    public function generateSlideVoiceOver(Request $request, Course $course, Slide $slide, VoiceOverService $voiceOverService) 
    {
        $slide->load('chapter');

        abort_if(!$slide->chapter || $slide->chapter->course_id !== $course->id, 404);

        if (!$course->support_voice_over) {
            return redirect()
                ->back()
                ->withErrors([
                    'voice_over' => __('Voice over is not enabled for this course.'),
                ]);
        }

        $validated = $request->validate([
            'voice_over_text' => ['required', 'string', 'max:10000'],
        ]);

        $text = trim($validated['voice_over_text']);

        if (!$text) {
            return redirect()
                ->back()
                ->withErrors([
                    'voice_over_text' => __('Voice over text is required.'),
                ]);
        }

        $lang = $course->voice_over_lang ?: config('services.voice_over.default_lang', 'en-us');
        $voice = $course->voice_over_voice ?: config('services.voice_over.default_voice', 'default');

        try {
            $audio = $voiceOverService->generate($text, $lang, $voice);

            if (
                $slide->voice_over_audio_path &&
                Storage::disk('public')->exists($slide->voice_over_audio_path)
            ) {
                Storage::disk('public')->delete($slide->voice_over_audio_path);
            }

            $extension = $audio['extension'] ?? 'mp3';
            $mime = $audio['mime_type'] ?? $audio['mime'] ?? 'audio/mpeg';
            $binary = $audio['content'] ?? $audio['binary'] ?? null;

            if (!$binary) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'voice_over' => __('Generated audio content is empty.'),
                    ]);
            }

            $fileName = 'voice-over-' . $slide->id . '-' . Str::uuid() . '.' . $extension;
            $path = 'slides/voice-overs/' . $fileName;

            Storage::disk('public')->put($path, $binary);

            $slide->update([
                'voice_over_enabled' => true,
                'voice_over_text' => $text,
                'voice_over_audio_path' => $path,
                'voice_over_audio_name' => $fileName,
                'voice_over_audio_type' => $mime,
                'voice_over_audio_size' => Storage::disk('public')->size($path),
            ]);

            return redirect()
                ->route('courses.edit', [
                    'course' => $course->hashid,
                    'step' => 3,
                ])
                ->with('success', __('Voice over has been generated successfully.'));
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'voice_over' => $e->getMessage(),
                ])
                ->withInput();
        }
    }

    public function deleteSlideVoiceOver(Course $course, Slide $slide)
    {
        $slide->load('chapter');

        abort_if(!$slide->chapter || $slide->chapter->course_id !== $course->id, 404);

        try {
            if (
                $slide->voice_over_audio_path &&
                Storage::disk('public')->exists($slide->voice_over_audio_path)
            ) {
                Storage::disk('public')->delete($slide->voice_over_audio_path);
            }

            $slide->update([
                'voice_over_audio_path' => null,
                'voice_over_audio_name' => null,
                'voice_over_audio_type' => null,
                'voice_over_audio_size' => null,
            ]);

            return redirect()
                ->route('courses.edit', [
                    'course' => $course->hashid,
                    'step' => 3,
                ])
                ->with('success', __('Voice over audio has been deleted successfully.'));
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while deleting voice over audio.'),
                ]);
        }
    }


    public function destroySlide(Course $course, Slide $slide)
    {
        abort_if((int) $slide->chapter?->course_id !== (int) $course->id, 404);

        DB::transaction(function () use ($slide) {
            $chapter = $slide->chapter;

            $this->deleteSlideFile($slide);

            if (
                $slide->voice_over_audio_path &&
                Storage::disk('public')->exists($slide->voice_over_audio_path)
            ) {
                Storage::disk('public')->delete($slide->voice_over_audio_path);
            }

            $slide->questions()->delete();
            $slide->delete();

            $this->refreshSlideSortOrders($chapter);
        });

        return $this->redirectToSlidesStep($course, 'Slide has been deleted successfully.');
    }

    public function reorderSlides(Request $request, Course $course, Chapter $chapter)
    {
        abort_if((int) $chapter->course_id !== (int) $course->id, 404);

        $validated = $request->validate([
            'slides' => ['required', 'array'],
            'slides.*.id' => [
                'required',
                'integer',
                Rule::exists('slides', 'id')->where('chapter_id', $chapter->id),
            ],
            'slides.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $chapter) {
            foreach ($validated['slides'] as $slideData) {
                Slide::where('id', $slideData['id'])
                    ->where('chapter_id', $chapter->id)
                    ->update([
                        'sort_order' => $slideData['sort_order'],
                    ]);
            }
        });

        return $this->redirectToSlidesStep($course, 'Slides order has been updated successfully.');
    }


    private function buildPlanDetailsTree($details, $parentId = null)
    {
        return $details
            ->where('parent_id', $parentId)
            ->values()
            ->map(function ($detail) use ($details) {
                return [
                    'id' => $detail->id,
                    'name' => $detail->name,
                    'own_questions' => $detail->own_questions ?? 0,
                    'total_questions' => $detail->total_questions ?? 0,
                    'parent_id' => $detail->parent_id,
                    'children' => $this->buildPlanDetailsTree($details, $detail->id),
                ];
            })
            ->values();
    }

    private function createPlanDetailsRecursive(Plan $plan, array $details, $parentId = null): void
    {
        foreach ($details as $detailData) {
            $detail = PlanDetail::create([
                'plan_id' => $plan->id,
                'name' => $detailData['name'],
                'own_questions' => $detailData['own_questions'] ?? 0,
                'total_questions' => 0,
                'parent_id' => $parentId,
            ]);

            if (!empty($detailData['children'])) {
                $this->createPlanDetailsRecursive(
                    $plan,
                    $detailData['children'],
                    $detail->id
                );
            }
        }
    }




    // plan 

    public function updatePlan(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'next_step' => ['nullable', 'integer', 'in:4,5'],
        ]);

        $plan = $course->plan;

        $data = [
            'name' => $validated['name'] ?? ($course->title . ' Plan'),
            'is_active' => $request->boolean('is_active'),
        ];

        if (!$plan) {
            Plan::create([
                'course_id' => $course->id,
                ...$data,
            ]);
        } else {
            $plan->update($data);
        }

        return redirect()
            ->route('courses.edit', [
                'course' => $course->hashid,
                'step' => $validated['next_step'] ?? 4,
            ])
            ->with('success', __('Plan has been updated successfully.'));
    }

    public function storePlanDetail(Request $request, Course $course)
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:plan_details,id'],
            'name' => ['required', 'string', 'max:255'],
            'own_questions' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan = $course->plan;

        if (!$plan) {
            $plan = Plan::create([
                'course_id' => $course->id,
                'name' => $course->title . ' Plan',
                'is_active' => true,
            ]);
        }

        if (!empty($validated['parent_id'])) {
            $parent = PlanDetail::where('id', $validated['parent_id'])
                ->where('plan_id', $plan->id)
                ->firstOrFail();

            $parentId = $parent->id;
        } else {
            $parentId = null;
        }

        PlanDetail::create([
            'plan_id' => $plan->id,
            'name' => $validated['name'],
            'parent_id' => $parentId,
            'own_questions' => $validated['own_questions'] ?? 0,
            'total_questions' => $validated['own_questions'] ?? 0,
        ]);

        $this->updatePlanTotals($plan);

        return redirect()
            ->route('courses.edit', ['course' => $course->hashid, 'step' => 4])
            ->with('success', __('Plan detail has been created successfully.'));
    }

    public function updatePlanDetail(Request $request, Course $course, PlanDetail $detail)
    {
        $plan = $course->plan;

        abort_if(!$plan || $detail->plan_id !== $plan->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'own_questions' => ['nullable', 'integer', 'min:0'],
        ]);

        $detail->update([
            'name' => $validated['name'],
            'own_questions' => $validated['own_questions'] ?? 0,
        ]);

        $this->updatePlanTotals($plan);

        return redirect()
            ->route('courses.edit', ['course' => $course->hashid, 'step' => 4])
            ->with('success', __('Plan detail has been updated successfully.'));
    }

    public function destroyPlanDetail(Course $course, PlanDetail $detail)
    {
        $plan = $course->plan;

        abort_if(!$plan || $detail->plan_id !== $plan->id, 404);

        $detail->delete();

        $this->updatePlanTotals($plan);

        return redirect()
            ->route('courses.edit', ['course' => $course->hashid, 'step' => 4])
            ->with('success', __('Plan detail has been deleted successfully.'));
    }

    private function updatePlanTotals(Plan $plan): void
    {
        $details = $plan->details()->get();

        $calculate = function ($parentId = null) use (&$calculate, $details) {
            $items = $details->where('parent_id', $parentId);

            $sum = 0;

            foreach ($items as $item) {
                $childrenTotal = $calculate($item->id);

                $total = (int) $item->own_questions + (int) $childrenTotal;

                $item->update([
                    'total_questions' => $total,
                ]);

                $sum += $total;
            }

            return $sum;
        };

        $calculate(null);
    }

    // show

    public function show($hashid)
    {
        $courseId = Hashids::decode($hashid);

        if (empty($courseId)) {
            abort(404);
        }

        $course = Course::with([
            'category',
            'tags',

            'chapters' => function ($query) {
                $query
                    ->whereHas('slides')
                    ->orderBy('sort_order');
            },

            'chapters.slides' => function ($query) {
                $query->orderBy('sort_order');
            },

            'chapters.slides.questions' => function ($query) {
                $query->orderBy('sort_order');
            },
        ])->findOrFail($courseId[0]);

        return inertia('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'hashid' => $course->hashid,

                // Training information
                'ref' => $course->ref,
                'method' => $course->method,
                'duration_hours' => $course->duration_hours,
                'passing_score' => $course->passing_score,
                'syllabus' => $course->syllabus,
                'syllabus_url' => $course->syllabus
                    ? asset('storage/' . $course->syllabus)
                    : null,
                'syllabus_name' => $course->syllabus
                    ? basename($course->syllabus)
                    : null,

                // General information
                'title' => $course->title,
                'description' => $course->description,
                'image' => $course->image,
                'image_url' => $course->image
                    ? asset('storage/' . $course->image)
                    : null,

                'category_id' => $course->category_id,
                'category' => $course->category,
                'tags' => $course->tags,

                'type' => $course->type,
                'training_type' => $course->training_type,
                'skill_level' => $course->skill_level,
                'course_focus' => $course->course_focus,
                'language' => $course->language,
                'price' => $course->price,
                'currency' => $course->currency,
                'is_published' => (bool) $course->is_published,

                // Voice over
                'support_voice_over' => (bool) $course->support_voice_over,
                'voice_over_auto_play' => (bool) $course->voice_over_auto_play,
                'voice_over_provider' => $course->voice_over_provider ?: 'voicerss',
                'voice_over_lang' => $course->voice_over_lang ?: 'en-us',
                'voice_over_voice' => $course->voice_over_voice ?: 'default',

                'created_at' => $course->created_at,

                'chapters' => $course->chapters
                    ->map(function ($chapter) {
                        return [
                            'id' => $chapter->id,
                            'title' => $chapter->title,
                            'description' => $chapter->description,
                            'sort_order' => $chapter->sort_order,

                            'slides' => $chapter->slides
                                ->map(function ($slide) {
                                    return [
                                        'id' => $slide->id,
                                        'chapter_id' => $slide->chapter_id,
                                        'title' => $slide->title,
                                        'lesson_type' => $slide->lesson_type,
                                        'content' => $slide->content,

                                        'video_url' => $slide->video_url,
                                        'video_duration' => $slide->video_duration,

                                        'file_path' => $slide->file_path,
                                        'file_url' => $slide->file_path
                                            ? asset('storage/' . $slide->file_path)
                                            : null,
                                        'file_name' => $slide->file_name,
                                        'file_type' => $slide->file_type,
                                        'file_size' => $slide->file_size,

                                        'external_url' => $slide->external_url,

                                        'is_preview' => (bool) $slide->is_preview,
                                        'is_mandatory' => (bool) $slide->is_mandatory,
                                        'is_downloadable' => (bool) $slide->is_downloadable,
                                        'is_active' => (bool) $slide->is_active,
                                        'sort_order' => $slide->sort_order,

                                        'questions' => $slide->questions
                                            ->map(function ($question) {
                                                return [
                                                    'id' => $question->id,
                                                    'question_type' => $question->question_type ?: 'qcm',
                                                    'question' => $question->question,
                                                    'option_a' => $question->option_a,
                                                    'option_b' => $question->option_b,
                                                    'option_c' => $question->option_c,
                                                    'correct_answer' => $question->correct_answer,
                                                    'explanation' => $question->explanation,
                                                    'sort_order' => $question->sort_order,
                                                ];
                                            })
                                            ->values(),

                                        'voice_over_enabled' => (bool) $slide->voice_over_enabled,
                                        'voice_over_text' => $slide->voice_over_text,
                                        'voice_over_audio_path' => $slide->voice_over_audio_path,
                                        'voice_over_audio_url' => $slide->voice_over_audio_path
                                            ? asset('storage/' . $slide->voice_over_audio_path)
                                            : null,
                                        'voice_over_audio_name' => $slide->voice_over_audio_name,
                                        'voice_over_audio_type' => $slide->voice_over_audio_type,
                                        'voice_over_audio_size' => $slide->voice_over_audio_size,
                                    ];
                                })
                                ->values(),
                        ];
                    })
                    ->values(),
            ],
        ]);
    }


    private function validateSlideRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'lesson_type' => [
                'required',
                'string',
                Rule::in([
                    'text',
                    'video',
                    'file',
                    'link',
                    'quiz',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Text slide
            |--------------------------------------------------------------------------
            */
            'content' => [
                'nullable',
                'string',
                Rule::requiredIf(
                    fn () => $request->input('lesson_type') === 'text'
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Video slide
            |--------------------------------------------------------------------------
            */
            'video_url' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(
                    fn () => $request->input('lesson_type') === 'video'
                ),
            ],

            'video_duration' => [
                'nullable',
                'integer',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | File slide
            |--------------------------------------------------------------------------
            */
            'file' => [
                'nullable',
                'file',
                'max:10240',

                Rule::requiredIf(function () use ($request) {
                    return $request->input('lesson_type') === 'file'
                        && !$request->input('file_path');
                }),
            ],

            'file_path' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | External link slide
            |--------------------------------------------------------------------------
            */
            'external_url' => [
                'nullable',
                'string',
                'max:1000',

                Rule::requiredIf(
                    fn () => $request->input('lesson_type') === 'link'
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Slide settings
            |--------------------------------------------------------------------------
            */
            'is_preview' => [
                'nullable',
                'boolean',
            ],

            'is_mandatory' => [
                'nullable',
                'boolean',
            ],

            'is_downloadable' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Voice over
            |--------------------------------------------------------------------------
            */
            'voice_over_enabled' => [
                'nullable',
                'boolean',
            ],

            'voice_over_text' => [
                'nullable',
                'string',
                'max:10000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Quiz questions
            |--------------------------------------------------------------------------
            */
            'questions' => [
                'nullable',
                'array',
                'max:3',

                Rule::requiredIf(
                    fn () => $request->input('lesson_type') === 'quiz'
                ),
            ],

            'questions.*.question_type' => [
                'nullable',
                'string',
                Rule::in([
                    'qcm',
                    'true_false',
                ]),
            ],

            'questions.*.question' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'questions.*.option_a' => [
                'nullable',
                'string',
                'max:255',
            ],

            'questions.*.option_b' => [
                'nullable',
                'string',
                'max:255',
            ],

            'questions.*.option_c' => [
                'nullable',
                'string',
                'max:255',
            ],

            'questions.*.correct_answer' => [
                'nullable',
                'string',
                'max:20',
            ],

            'questions.*.explanation' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Conditional quiz validation
        |--------------------------------------------------------------------------
        */
        $validator->after(function ($validator) use ($request) {
            if ($request->input('lesson_type') !== 'quiz') {
                return;
            }

            $questions = $request->input('questions', []);

            if (!is_array($questions) || count($questions) === 0) {
                $validator->errors()->add(
                    'questions',
                    'Please add at least one question.'
                );

                return;
            }

            if (count($questions) > 3) {
                $validator->errors()->add(
                    'questions',
                    'You can add a maximum of 3 questions per slide.'
                );
            }

            foreach (array_slice($questions, 0, 3) as $index => $questionData) {
                $questionNumber = $index + 1;

                $questionType = $questionData['question_type'] ?? 'qcm';
                $questionText = trim((string) ($questionData['question'] ?? ''));
                $correctAnswer = strtolower(
                    trim((string) ($questionData['correct_answer'] ?? ''))
                );

                /*
                |--------------------------------------------------------------------------
                | Question text
                |--------------------------------------------------------------------------
                */
                if ($questionText === '') {
                    $validator->errors()->add(
                        "questions.{$index}.question",
                        "Question {$questionNumber} is required."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | QCM validation
                |--------------------------------------------------------------------------
                */
                if ($questionType === 'qcm') {
                    $optionA = trim((string) ($questionData['option_a'] ?? ''));
                    $optionB = trim((string) ($questionData['option_b'] ?? ''));
                    $optionC = trim((string) ($questionData['option_c'] ?? ''));

                    if ($optionA === '') {
                        $validator->errors()->add(
                            "questions.{$index}.option_a",
                            "Option A is required for question {$questionNumber}."
                        );
                    }

                    if ($optionB === '') {
                        $validator->errors()->add(
                            "questions.{$index}.option_b",
                            "Option B is required for question {$questionNumber}."
                        );
                    }

                    if (!in_array($correctAnswer, ['a', 'b', 'c'], true)) {
                        $validator->errors()->add(
                            "questions.{$index}.correct_answer",
                            "Select a valid answer for question {$questionNumber}."
                        );
                    }

                    if ($correctAnswer === 'c' && $optionC === '') {
                        $validator->errors()->add(
                            "questions.{$index}.option_c",
                            "Option C is required because it is selected as the correct answer."
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | True / False validation
                |--------------------------------------------------------------------------
                */
                if ($questionType === 'true_false') {
                    if (!in_array($correctAnswer, ['true', 'false'], true)) {
                        $validator->errors()->add(
                            "questions.{$index}.correct_answer",
                            "Select True or False for question {$questionNumber}."
                        );
                    }
                }
            }
        });

        return $validator->validate();
    }

    private function syncSlideQuestions(Slide $slide, Request $request): void
    {
    
        $slide->questions()->delete();

        if ($request->input('lesson_type') !== 'quiz') {
            return;
        }

        $questions = collect($request->input('questions', []))
            ->filter(function ($questionData) {
                return !empty(trim((string) ($questionData['question'] ?? '')));
            })
            ->take(3)
            ->values();

        foreach ($questions as $index => $questionData) {
            $questionType = $questionData['question_type'] ?? 'qcm';

            if (!in_array($questionType, ['qcm', 'true_false'], true)) {
                $questionType = 'qcm';
            }

            $correctAnswer = strtolower(
                trim((string) ($questionData['correct_answer'] ?? ''))
            );

            /*
            |--------------------------------------------------------------------------
            | True / False question
            |--------------------------------------------------------------------------
            */
            if ($questionType === 'true_false') {
                $optionA = null;
                $optionB = null;
                $optionC = null;

                $correctAnswer = in_array(
                    $correctAnswer,
                    ['true', 'false'],
                    true
                )
                    ? $correctAnswer
                    : null;
            } else {
                /*
                |--------------------------------------------------------------------------
                | QCM question
                |--------------------------------------------------------------------------
                */
                $optionA = !empty($questionData['option_a'])
                    ? trim((string) $questionData['option_a'])
                    : null;

                $optionB = !empty($questionData['option_b'])
                    ? trim((string) $questionData['option_b'])
                    : null;

                $optionC = !empty($questionData['option_c'])
                    ? trim((string) $questionData['option_c'])
                    : null;

                $correctAnswer = in_array(
                    $correctAnswer,
                    ['a', 'b', 'c'],
                    true
                )
                    ? $correctAnswer
                    : null;
            }

            $slide->questions()->create([
                'question_type' => $questionType,
                'question' => trim((string) $questionData['question']),
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'correct_answer' => $correctAnswer,

                'explanation' => !empty($questionData['explanation'])
                    ? trim((string) $questionData['explanation'])
                    : null,

                'sort_order' => $index + 1,
            ]);
        }
    }
    private function handleSlideFileUpload(Request $request): array
    {
        if (!$request->hasFile('file')) {
            return [];
        }

        $file = $request->file('file');

        $path = $file->store('slides/files', 'public');

        return [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
        ];
    }

    private function deleteSlideFile(Slide $slide): void
    {
        if ($slide->file_path && Storage::disk('public')->exists($slide->file_path)) {
            Storage::disk('public')->delete($slide->file_path);
        }
    }

    private function refreshSlideSortOrders(?Chapter $chapter): void
    {
        if (!$chapter) {
            return;
        }

        $slides = $chapter->slides()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($slides as $index => $slide) {
            $slide->update([
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function redirectToSlidesStep(Course $course, string $message)
    {
        return redirect()
            ->route('courses.edit', [
                'course' => $course->hashid,
                'step' => 3,
            ])
            ->with('success', $message);
    }

}