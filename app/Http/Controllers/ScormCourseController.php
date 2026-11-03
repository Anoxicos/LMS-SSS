<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Plan;
use App\Models\PlanDetail;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Vinkla\Hashids\Facades\Hashids;
use ZipArchive;

class ScormCourseController extends Controller
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
            ->where('content_type', 'scorm')
            ->latest()
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'hashid' => $course->hashid,
                'ref' => $course->ref,
                'title' => $course->title,
                'description' => $course->description,
                'image' => $course->image,
                'image_url' => $course->image_url,
                'category' => $course->category,
                'tags' => $course->tags,
                'content_type' => $course->content_type,
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
                'scorm_zip_path' => $course->scorm_zip_path,
                'scorm_extract_path' => $course->scorm_extract_path,
                'scorm_entry_file' => $course->scorm_entry_file,
                'scorm_version' => $course->scorm_version,
                'scorm_identifier' => $course->scorm_identifier,
                'scorm_title' => $course->scorm_title,
                'scorm_launch_url' => $course->scorm_launch_url,
                'created_at' => $course->created_at,
            ]);

        return inertia('Courses/Scorm/IndexScorm', [
            'courses' => $courses,
        ]);
    }

    public function create()
    {
        return inertia('Courses/Scorm/Create', [
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
        $validated = $this->validateScormCourse($request, true);

        $imagePath = null;
        $syllabusPath = null;
        $scormData = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('courses/images', 'public');
            }

            if ($request->hasFile('syllabus')) {
                $syllabusPath = $request
                    ->file('syllabus')
                    ->store('courses/syllabuses', 'public');
            }

            $scormData = $this->handleCourseScormUpload($request->file('scorm_file'));

            $course = Course::create([
                'category_id' => $validated['category_id'],
                'ref' => trim($validated['ref']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'image' => $imagePath,
                'content_type' => 'scorm',
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
                'support_voice_over' => false,
                'voice_over_auto_play' => false,
                'voice_over_provider' => 'voicerss',
                'voice_over_lang' => 'en-us',
                'voice_over_voice' => 'default',
                'scorm_zip_path' => $scormData['scorm_zip_path'],
                'scorm_extract_path' => $scormData['scorm_extract_path'],
                'scorm_entry_file' => $scormData['scorm_entry_file'],
                'scorm_version' => $scormData['scorm_version'],
                'scorm_identifier' => $scormData['scorm_identifier'],
                'scorm_title' => $scormData['scorm_title'],
            ]);

            $course->tags()->sync($validated['tags'] ?? []);

            DB::commit();

            return redirect()
                ->route('courses.scorm.edit', [
                    'hashid' => $course->hashid,
                    'step' => 2,
                ])
                ->with('success', __('SCORM course has been created successfully. You can now configure its plan.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            if ($syllabusPath) {
                Storage::disk('public')->delete($syllabusPath);
            }

            $this->deleteScormFiles($scormData);

            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while creating the SCORM course.'),
                ])
                ->withInput();
        }
    }

    public function edit(Request $request, string $hashid)
    {
        $course = $this->findScormCourse($hashid, [
            'category',
            'tags',
            'plan.details',
        ]);

        $step = max(1, min(3, (int) $request->query('step', 1)));

        return inertia('Courses/Scorm/Edit', [
            'step' => $step,

            'categories' => Category::where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),

            'tags' => Tag::select('id', 'name')
                ->orderBy('name')
                ->get(),

            'course' => $this->serializeScormCourse($course),
        ]);
    }

    public function update(Request $request, string $hashid)
    {
        $course = $this->findScormCourse($hashid);
        $validated = $this->validateScormCourse($request, false, $course);

        $newImagePath = null;
        $oldImagePath = null;
        $newSyllabusPath = null;
        $oldSyllabusPath = null;
        $newScormData = null;
        $oldScormZipPath = null;
        $oldScormExtractPath = null;

        try {
            DB::beginTransaction();

            $imagePath = $course->image;

            if ($request->boolean('remove_image') && $course->image) {
                $oldImagePath = $course->image;
                $imagePath = null;
            }

            if ($request->hasFile('image')) {
                $oldImagePath = $course->image;
                $newImagePath = $request->file('image')->store('courses/images', 'public');
                $imagePath = $newImagePath;
            }

            $syllabusPath = $course->syllabus;

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

            $scormPayload = [];

            if ($request->hasFile('scorm_file')) {
                $oldScormZipPath = $course->scorm_zip_path;
                $oldScormExtractPath = $course->scorm_extract_path;

                $newScormData = $this->handleCourseScormUpload($request->file('scorm_file'));

                $scormPayload = [
                    'scorm_zip_path' => $newScormData['scorm_zip_path'],
                    'scorm_extract_path' => $newScormData['scorm_extract_path'],
                    'scorm_entry_file' => $newScormData['scorm_entry_file'],
                    'scorm_version' => $newScormData['scorm_version'],
                    'scorm_identifier' => $newScormData['scorm_identifier'],
                    'scorm_title' => $newScormData['scorm_title'],
                ];
            }

            $course->update(array_merge([
                'category_id' => $validated['category_id'],
                'ref' => trim($validated['ref']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'image' => $imagePath,
                'content_type' => 'scorm',
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
                'support_voice_over' => false,
                'voice_over_auto_play' => false,
                'voice_over_provider' => 'voicerss',
                'voice_over_lang' => 'en-us',
                'voice_over_voice' => 'default',
            ], $scormPayload));

            $course->tags()->sync($validated['tags'] ?? []);

            DB::commit();

            if ($oldImagePath && $oldImagePath !== $imagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            if ($oldSyllabusPath && $oldSyllabusPath !== $syllabusPath) {
                Storage::disk('public')->delete($oldSyllabusPath);
            }

            if ($oldScormZipPath) {
                Storage::disk('public')->delete($oldScormZipPath);
            }

            if ($oldScormExtractPath) {
                File::deleteDirectory(storage_path('app/public/' . $oldScormExtractPath));
            }

            return redirect()
                ->route('courses.scorm.edit', [
                    'hashid' => $course->hashid,
                    'step' => $validated['next_step'] ?? 1,
                ])
                ->with('success', __('SCORM course information has been updated successfully.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            if ($newSyllabusPath) {
                Storage::disk('public')->delete($newSyllabusPath);
            }

            $this->deleteScormFiles($newScormData);

            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while updating the SCORM course.'),
                ])
                ->withInput();
        }
    }

    public function updatePlan(Request $request, string $hashid)
    {
        $course = $this->findScormCourse($hashid);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'next_step' => ['nullable', 'integer', Rule::in([2, 3])],
        ]);

        $plan = $this->getOrCreatePlan($course);

        $plan->update([
            'name' => $validated['name'] ?: ($course->title . ' Plan'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('courses.scorm.edit', [
                'hashid' => $course->hashid,
                'step' => $validated['next_step'] ?? 2,
            ])
            ->with('success', __('SCORM course plan has been updated successfully.'));
    }

    public function storePlanDetail(Request $request, string $hashid)
    {
        $course = $this->findScormCourse($hashid);
        $plan = $this->getOrCreatePlan($course);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'own_questions' => ['nullable', 'integer', 'min:0'],
        ]);

        $parentId = null;

        if (!empty($validated['parent_id'])) {
            $parent = PlanDetail::query()
                ->whereKey($validated['parent_id'])
                ->where('plan_id', $plan->id)
                ->firstOrFail();

            $parentId = $parent->id;
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
            ->route('courses.scorm.edit', [
                'hashid' => $course->hashid,
                'step' => 2,
            ])
            ->with('success', __('Plan detail has been created successfully.'));
    }

    public function updatePlanDetail(Request $request, string $hashid, PlanDetail $detail)
    {
        $course = $this->findScormCourse($hashid);
        $plan = $course->plan;

        abort_if(!$plan || (int) $detail->plan_id !== (int) $plan->id, 404);

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
            ->route('courses.scorm.edit', [
                'hashid' => $course->hashid,
                'step' => 2,
            ])
            ->with('success', __('Plan detail has been updated successfully.'));
    }

    public function destroyPlanDetail(string $hashid, PlanDetail $detail)
    {
        $course = $this->findScormCourse($hashid);
        $plan = $course->plan;

        abort_if(!$plan || (int) $detail->plan_id !== (int) $plan->id, 404);

        DB::transaction(function () use ($detail, $plan) {
            $this->deletePlanDetailTree($detail);
            $this->updatePlanTotals($plan);
        });

        return redirect()
            ->route('courses.scorm.edit', [
                'hashid' => $course->hashid,
                'step' => 2,
            ])
            ->with('success', __('Plan detail has been deleted successfully.'));
    }

    public function show(string $hashid)
    {
        $course = $this->findScormCourse($hashid, [
            'category',
            'tags',
            'plan.details',
        ]);

        return inertia('Courses/Scorm/Show', [
            'course' => $this->serializeScormCourse($course),
        ]);
    }

    public function destroy(string $hashid)
    {
        $course = $this->findScormCourse($hashid);

        $imagePath = $course->image;
        $syllabusPath = $course->syllabus;
        $scormZipPath = $course->scorm_zip_path;
        $scormExtractPath = $course->scorm_extract_path;

        try {
            DB::transaction(function () use ($course) {
                $course->tags()->detach();
                $course->delete();
            });

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            if ($syllabusPath) {
                Storage::disk('public')->delete($syllabusPath);
            }

            if ($scormZipPath) {
                Storage::disk('public')->delete($scormZipPath);
            }

            if ($scormExtractPath) {
                File::deleteDirectory(storage_path('app/public/' . $scormExtractPath));
            }

            return redirect()
                ->route('courses.scorm.index')
                ->with('success', __('SCORM course has been deleted successfully.'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withErrors([
                    'error' => __('An error occurred while deleting the SCORM course.'),
                ]);
        }
    }

    private function validateScormCourse(Request $request, bool $creating, ?Course $course = null): array
    {
        return $request->validate([
            'ref' => [
                'required',
                'string',
                'max:100',
                Rule::unique('courses', 'ref')->ignore($course?->id),
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
            'syllabus' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_syllabus' => ['nullable', 'boolean'],
            'skill_level' => ['nullable', 'string', 'max:100'],
            'course_focus' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:20'],
            'is_published' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'scorm_file' => [
                $creating || !$course?->scorm_zip_path ? 'required' : 'nullable',
                'file',
                'mimes:zip',
                'max:512000',
            ],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'next_step' => ['nullable', 'integer', Rule::in([1, 2])],
        ]);
    }

    private function findScormCourse(string $hashid, array $relations = []): Course
    {
        $decoded = Hashids::decode($hashid);

        abort_if(empty($decoded), 404);

        $course = Course::with($relations)->findOrFail($decoded[0]);

        abort_if($course->content_type !== 'scorm', 404);

        return $course;
    }

    private function serializeScormCourse(Course $course): array
    {
        $details = $course->plan?->details ?? collect();

        return [
            'id' => $course->id,
            'hashid' => $course->hashid,
            'category_id' => $course->category_id,
            'ref' => $course->ref,
            'title' => $course->title,
            'description' => $course->description,
            'image' => $course->image,
            'image_url' => $course->image_url,
            'category' => $course->category,
            'tags' => $course->tags,
            'content_type' => $course->content_type,
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
            'scorm_zip_path' => $course->scorm_zip_path,
            'scorm_extract_path' => $course->scorm_extract_path,
            'scorm_entry_file' => $course->scorm_entry_file,
            'scorm_version' => $course->scorm_version,
            'scorm_identifier' => $course->scorm_identifier,
            'scorm_title' => $course->scorm_title,
            'scorm_launch_url' => $course->scorm_launch_url,
            'plan' => [
                'id' => $course->plan?->id,
                'name' => $course->plan?->name ?? '',
                'is_active' => $course->plan?->is_active ?? true,
                'details' => $this->buildPlanDetailsTree($details),
            ],
            'created_at' => $course->created_at,
        ];
    }

    private function buildPlanDetailsTree($details, $parentId = null)
    {
        return $details
            ->where('parent_id', $parentId)
            ->values()
            ->map(fn (PlanDetail $detail) => [
                'id' => $detail->id,
                'name' => $detail->name,
                'own_questions' => (int) ($detail->own_questions ?? 0),
                'total_questions' => (int) ($detail->total_questions ?? 0),
                'parent_id' => $detail->parent_id,
                'children' => $this->buildPlanDetailsTree($details, $detail->id),
            ])
            ->values();
    }

    private function getOrCreatePlan(Course $course): Plan
    {
        $plan = Plan::withTrashed()
            ->where('course_id', $course->id)
            ->first();

        if ($plan) {
            if ($plan->trashed()) {
                $plan->restore();
            }

            return $plan;
        }

        return Plan::create([
            'course_id' => $course->id,
            'name' => $course->title . ' Plan',
            'is_active' => true,
        ]);
    }

    private function updatePlanTotals(Plan $plan): void
    {
        $details = $plan->details()->get();

        $calculate = function ($parentId = null) use (&$calculate, $details): int {
            $sum = 0;

            foreach ($details->where('parent_id', $parentId) as $item) {
                $childrenTotal = $calculate($item->id);
                $total = (int) $item->own_questions + $childrenTotal;

                $item->update([
                    'total_questions' => $total,
                ]);

                $sum += $total;
            }

            return $sum;
        };

        $calculate(null);
    }

    private function deletePlanDetailTree(PlanDetail $detail): void
    {
        foreach ($detail->children()->get() as $child) {
            $this->deletePlanDetailTree($child);
        }

        $detail->delete();
    }

    private function handleCourseScormUpload($uploadedFile): array
    {
        $zipPath = $uploadedFile->store('courses/scorm/zips', 'public');
        $extractFolder = 'courses/scorm/packages/' . Str::uuid();
        $absoluteExtractPath = storage_path('app/public/' . $extractFolder);

        File::ensureDirectoryExists($absoluteExtractPath, 0755, true);

        $zip = new ZipArchive();
        $absoluteZipPath = storage_path('app/public/' . $zipPath);

        if ($zip->open($absoluteZipPath) !== true) {
            Storage::disk('public')->delete($zipPath);
            File::deleteDirectory($absoluteExtractPath);
            throw new \RuntimeException('Unable to open SCORM ZIP file.');
        }

        try {
            $this->assertSafeZipEntries($zip);

            if (!$zip->extractTo($absoluteExtractPath)) {
                throw new \RuntimeException('Unable to extract the SCORM package.');
            }
        } finally {
            $zip->close();
        }

        $manifestPath = $absoluteExtractPath . '/imsmanifest.xml';

        if (!File::exists($manifestPath)) {
            Storage::disk('public')->delete($zipPath);
            File::deleteDirectory($absoluteExtractPath);
            throw new \RuntimeException('Invalid SCORM package: imsmanifest.xml was not found at the package root.');
        }

        libxml_use_internal_errors(true);
        $manifest = simplexml_load_file($manifestPath, 'SimpleXMLElement', LIBXML_NONET);
        libxml_clear_errors();

        if (!$manifest) {
            Storage::disk('public')->delete($zipPath);
            File::deleteDirectory($absoluteExtractPath);
            throw new \RuntimeException('Invalid SCORM package: unable to read imsmanifest.xml.');
        }

        $resources = $manifest->xpath('//*[local-name()="resource"]');
        $entryFile = null;
        $identifier = null;

        foreach ($resources as $resource) {
            $attributes = $resource->attributes();

            if (!isset($attributes['href'])) {
                continue;
            }

            $entryFile = ltrim(str_replace('\\', '/', (string) $attributes['href']), '/');
            $identifier = isset($attributes['identifier'])
                ? (string) $attributes['identifier']
                : null;
            break;
        }

        if (!$entryFile || !File::exists($absoluteExtractPath . '/' . $entryFile)) {
            Storage::disk('public')->delete($zipPath);
            File::deleteDirectory($absoluteExtractPath);
            throw new \RuntimeException('Invalid SCORM package: the launch file declared in the manifest was not found.');
        }

        $titleNode = $manifest->xpath('//*[local-name()="organization"]/*[local-name()="title"]');
        $versionNode = $manifest->xpath('//*[local-name()="schemaversion"]');

        return [
            'scorm_zip_path' => $zipPath,
            'scorm_extract_path' => $extractFolder,
            'scorm_entry_file' => $entryFile,
            'scorm_version' => isset($versionNode[0])
                ? trim((string) $versionNode[0])
                : 'SCORM 1.2',
            'scorm_identifier' => $identifier,
            'scorm_title' => isset($titleNode[0])
                ? trim((string) $titleNode[0])
                : null,
        ];
    }

    private function assertSafeZipEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($index));

            if (
                $entry === '' ||
                str_starts_with($entry, '/') ||
                preg_match('/^[A-Za-z]:\//', $entry) ||
                in_array('..', explode('/', $entry), true)
            ) {
                throw new \RuntimeException('Invalid SCORM package: unsafe ZIP path detected.');
            }
        }
    }

    private function deleteScormFiles(?array $scormData): void
    {
        if (!$scormData) {
            return;
        }

        if (!empty($scormData['scorm_zip_path'])) {
            Storage::disk('public')->delete($scormData['scorm_zip_path']);
        }

        if (!empty($scormData['scorm_extract_path'])) {
            File::deleteDirectory(storage_path('app/public/' . $scormData['scorm_extract_path']));
        }
    }
}
