<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\ScormTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScormTrackingController extends Controller
{
    public function commit(
        Request $request,
        string $enrollmentHashid
    ): JsonResponse {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:30'],
            'values' => ['required', 'array'],
        ]);

        $enrollmentId = Enrollment::decodeHashid($enrollmentHashid);

        if (!$enrollmentId) {
            abort(404, 'Enrollment not found.');
        }

        $values = $this->sanitizeValues($validated['values']);

        return DB::transaction(function () use (
            $request,
            $validated,
            $enrollmentId,
            $values
        ) {
            $enrollment = Enrollment::with([
                    'course',
                    'scormTracking',
                ])
                ->where('id', $enrollmentId)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$enrollment->course) {
                abort(404, 'Course not found.');
            }

            if (!$enrollment->course->isScorm()) {
                abort(422, 'This enrollment is not a SCORM course.');
            }

            $tracking = ScormTracking::query()
                ->where('enrollment_id', $enrollment->id)
                ->lockForUpdate()
                ->first();

            if (!$tracking) {
                $tracking = new ScormTracking([
                    'enrollment_id' => $enrollment->id,
                ]);
            }

            $existingValues = is_array($tracking->cmi_data)
                ? $tracking->cmi_data
                : [];

            /*
            |------------------------------------------------------------------
            | cmi.entry is supplied by the LMS at launch time.
            | It must never be persisted as a learner value.
            |------------------------------------------------------------------
            */
            unset(
                $existingValues['cmi.core.entry'],
                $existingValues['cmi.entry'],
                $values['cmi.core.entry'],
                $values['cmi.entry']
            );

            $mergedValues = array_merge($existingValues, $values);

            /*
            |------------------------------------------------------------------
            | The JavaScript API reports the API actually used by the package.
            | This is more reliable than the version stored during import.
            |------------------------------------------------------------------
            */
            $version = $this->resolveVersion(
                $enrollment->course->scorm_version,
                $validated['version'] ?? null,
                $mergedValues
            );

            $lessonStatus = $this->getStringValue(
                $mergedValues,
                'cmi.core.lesson_status'
            );

            $completionStatus = $this->getStringValue(
                $mergedValues,
                'cmi.completion_status'
            );

            $successStatus = $this->getStringValue(
                $mergedValues,
                'cmi.success_status'
            );

            $progressMeasure = $this->getFirstDecimalValue(
                $mergedValues,
                [
                    'cmi.progress_measure',
                    'cmi.core.progress_measure',
                    'cmi.progress',
                    'cmi.core.progress',
                ]
            );

            if ($progressMeasure !== null) {
                // Accept both 1.00 and 100 as 100 percent.
                if ($progressMeasure > 1) {
                    $progressMeasure = $progressMeasure / 100;
                }

                $progressMeasure = min(1, max(0, $progressMeasure));
            }

            /*
            |------------------------------------------------------------------
            | Do not depend only on the declared SCORM version.
            |
            | Some packages are declared as 1.2 but communicate completion with
            | SCORM 2004 fields. Completion is accepted when any valid completion
            | signal is received.
            |------------------------------------------------------------------
            */
            $reportedCompleted = $this->resolveCompletion(
                $lessonStatus,
                $completionStatus,
                $successStatus,
                $progressMeasure
            );

            // Completion is irreversible for the current enrollment.
            $isCompleted = (bool) $tracking->is_completed || $reportedCompleted;

            $tracking->fill([
                'scorm_version' => $version,
                'lesson_status' => $lessonStatus,
                'completion_status' => $completionStatus,
                'success_status' => $successStatus,
                'progress_measure' => $progressMeasure,

                'score_raw' => $this->getFirstDecimalValue(
                    $mergedValues,
                    [
                        'cmi.core.score.raw',
                        'cmi.score.raw',
                    ]
                ),

                'score_min' => $this->getFirstDecimalValue(
                    $mergedValues,
                    [
                        'cmi.core.score.min',
                        'cmi.score.min',
                    ]
                ),

                'score_max' => $this->getFirstDecimalValue(
                    $mergedValues,
                    [
                        'cmi.core.score.max',
                        'cmi.score.max',
                    ]
                ),

                'score_scaled' => $this->getDecimalValue(
                    $mergedValues,
                    'cmi.score.scaled'
                ),

                'lesson_location' => $this->getFirstStringValue(
                    $mergedValues,
                    [
                        'cmi.core.lesson_location',
                        'cmi.location',
                    ]
                ),

                'suspend_data' => $this->getStringValue(
                    $mergedValues,
                    'cmi.suspend_data'
                ),

                'session_time' => $this->getFirstStringValue(
                    $mergedValues,
                    [
                        'cmi.core.session_time',
                        'cmi.session_time',
                    ]
                ),

                'total_time' => $this->getFirstStringValue(
                    $mergedValues,
                    [
                        'cmi.core.total_time',
                        'cmi.total_time',
                    ]
                ),

                'exit_status' => $this->getFirstStringValue(
                    $mergedValues,
                    [
                        'cmi.core.exit',
                        'cmi.exit',
                    ]
                ),

                'cmi_data' => $mergedValues,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted
                    ? ($tracking->completed_at ?? now())
                    : null,
                'last_committed_at' => now(),
            ]);

            $tracking->save();

            $currentProgress = (float) ($enrollment->progress_percent ?? 0);

            $reportedProgress = $isCompleted
                ? 100
                : ($progressMeasure !== null
                    ? round($progressMeasure * 100, 2)
                    : $currentProgress);

            if ($isCompleted) {
                $enrollment->update([
                    'status' => 'completed',
                    'progress_percent' => 100,
                    'completed_at' => $enrollment->completed_at ?? now(),
                    'started_at' => $enrollment->started_at ?? now(),
                ]);
            } else {
                $enrollment->update([
                    'status' => 'in_progress',
                    'started_at' => $enrollment->started_at ?? now(),
                    'progress_percent' => max(
                        $currentProgress,
                        $reportedProgress
                    ),
                ]);
            }

            return response()->json([
                'saved' => true,
                'detected_version' => $version,
                'course_completed' => $isCompleted,
                'progress_percent' => $isCompleted
                    ? 100
                    : max($currentProgress, $reportedProgress),
                'lesson_status' => $lessonStatus,
                'completion_status' => $completionStatus,
                'success_status' => $successStatus,
                'progress_measure' => $progressMeasure,
                'can_start_exam' => (
                    $isCompleted &&
                    (int) $enrollment->attempts_rest > 0
                ),
            ]);
        });
    }

    private function sanitizeValues(array $values): array
    {
        $cleanValues = [];

        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (!str_starts_with($key, 'cmi.')) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            if (in_array($key, [
                'cmi.core.entry',
                'cmi.entry',
            ], true)) {
                continue;
            }

            $cleanValues[$key] = $value === null
                ? ''
                : (string) $value;
        }

        return $cleanValues;
    }

    private function resolveVersion(
        ?string $courseVersion,
        ?string $requestVersion,
        array $values
    ): string {
        $requestVersion = strtolower(trim((string) $requestVersion));

        // useScormApi.js sends the API actually used by the package.
        if (str_contains($requestVersion, '2004')) {
            return '2004';
        }

        if (str_contains($requestVersion, '1.2')) {
            return '1.2';
        }

        $completionStatus = strtolower(trim((string) (
            $values['cmi.completion_status'] ?? ''
        )));

        $successStatus = strtolower(trim((string) (
            $values['cmi.success_status'] ?? ''
        )));

        $lessonStatus = strtolower(trim((string) (
            $values['cmi.core.lesson_status'] ?? ''
        )));

        if (
            !in_array($completionStatus, ['', 'unknown', 'not attempted'], true) ||
            !in_array($successStatus, ['', 'unknown'], true)
        ) {
            return '2004';
        }

        if (!in_array($lessonStatus, ['', 'not attempted'], true)) {
            return '1.2';
        }

        $courseVersion = strtolower(trim((string) $courseVersion));

        return str_contains($courseVersion, '2004')
            ? '2004'
            : '1.2';
    }

    private function resolveCompletion(
        ?string $lessonStatus,
        ?string $completionStatus,
        ?string $successStatus,
        ?float $progressMeasure
    ): bool {
        $lessonStatus = strtolower(trim((string) $lessonStatus));
        $completionStatus = strtolower(trim((string) $completionStatus));
        $successStatus = strtolower(trim((string) $successStatus));

        if (in_array($lessonStatus, ['completed', 'passed'], true)) {
            return true;
        }

        if ($completionStatus === 'completed') {
            return true;
        }

        if ($progressMeasure !== null && $progressMeasure >= 1) {
            return true;
        }

        /*
         * success_status = passed alone is intentionally not sufficient.
         * The course must also report completion or 100 percent progress.
         */
        return false;
    }

    private function getStringValue(array $values, string $key): ?string
    {
        if (!array_key_exists($key, $values)) {
            return null;
        }

        $value = trim((string) $values[$key]);

        return $value !== '' ? $value : null;
    }

    private function getFirstStringValue(array $values, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->getStringValue($values, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function getDecimalValue(array $values, string $key): ?float
    {
        if (!array_key_exists($key, $values)) {
            return null;
        }

        if (!is_numeric($values[$key])) {
            return null;
        }

        return (float) $values[$key];
    }

    private function getFirstDecimalValue(array $values, array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = $this->getDecimalValue($values, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}
