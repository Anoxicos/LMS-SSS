<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SuperAdminPendingReminderController extends Controller
{
    /**
     * Return all pending items that require Super Admin attention.
     */
    public function index(): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Company Requests
        |--------------------------------------------------------------------------
        |
        | company_requests.status:
        | - pending
        | - approved
        | - rejected
        |
        */

        $pendingCompanyRequestsCount = DB::table('company_requests')
            ->whereNull('deleted_at')
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Individual Requests requiring attention
        |--------------------------------------------------------------------------
        |
        | An individual request is counted only once if:
        |
        | 1. Its global status is pending
        | OR
        | 2. At least one requested course is still pending.
        |
        | This avoids counting the same individual request several times.
        |
        */

        $pendingIndividualRequestsCount = DB::table('individual_requests as ir')
            ->whereNull('ir.deleted_at')
            ->where(function ($query) {
                $query
                    ->where('ir.status', 'pending')
                    ->orWhereExists(function ($subQuery) {
                        $subQuery
                            ->selectRaw('1')
                            ->from('individual_request_courses as irc')
                            ->whereColumn(
                                'irc.individual_request_id',
                                'ir.id'
                            )
                            ->where('irc.status', 'pending');
                    });
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending courses inside Individual Requests
        |--------------------------------------------------------------------------
        |
        | This number is informational only.
        | It is NOT added again to the global total.
        |
        */

        $pendingIndividualCoursesCount = DB::table(
            'individual_request_courses as irc'
        )
            ->join(
                'individual_requests as ir',
                'ir.id',
                '=',
                'irc.individual_request_id'
            )
            ->whereNull('ir.deleted_at')
            ->where('irc.status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Global total
        |--------------------------------------------------------------------------
        |
        | One pending company request = one item.
        | One individual request requiring attention = one item.
        |
        */

        $totalPending =
            $pendingCompanyRequestsCount +
            $pendingIndividualRequestsCount;

        $items = [];

        if ($pendingCompanyRequestsCount > 0) {
            $items[] = [
                'key' => 'company_requests',
                'label' => 'Company Requests',
                'count' => (int) $pendingCompanyRequestsCount,
                'description' => 'Company training requests waiting for review.',
                'icon' => 'ri-building-line',
            ];
        }

        if ($pendingIndividualRequestsCount > 0) {
            $items[] = [
                'key' => 'individual_requests',
                'label' => 'Individual Requests',
                'count' => (int) $pendingIndividualRequestsCount,
                'description' => sprintf(
                    '%d course(s) still waiting for a decision.',
                    $pendingIndividualCoursesCount
                ),
                'icon' => 'ri-user-line',

                'details' => [
                    'pending_courses' => (int) $pendingIndividualCoursesCount,
                ],
            ];
        }

        return response()->json([
            'has_pending' => $totalPending > 0,

            'total' => (int) $totalPending,

            'items' => $items,

            'summary' => [
                'company_requests' => (int) $pendingCompanyRequestsCount,

                'individual_requests' => (int) $pendingIndividualRequestsCount,

                'individual_courses' => (int) $pendingIndividualCoursesCount,
            ],

            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
