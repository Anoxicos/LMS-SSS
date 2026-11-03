<?php

namespace App\Http\Controllers;

use App\Exports\CompanyEnrollmentsExport;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Vinkla\Hashids\Facades\Hashids;

class CompanyEnrollmentExportController
    extends Controller
{
    public function export(
        Request $request,
        string $hashid
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'start_date' => [
                    'required',
                    'date',
                ],

                'end_date' => [
                    'required',
                    'date',
                    'after_or_equal:start_date',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $decoded =
            Hashids::decode(
                $hashid
            );

        abort_if(
            empty($decoded),
            404
        );

        $company =
            Company::findOrFail(
                $decoded[0]
            );

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $startDate =
            Carbon::parse(
                $validated['start_date']
            )->startOfDay();

        $endDate =
            Carbon::parse(
                $validated['end_date']
            )->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | File name
        |--------------------------------------------------------------------------
        */

        $fileName =
            'enrollments-' .
            Str::slug(
                $company->name
            ) .
            '-' .
            $startDate->format(
                'Y-m-d'
            ) .
            '-to-' .
            $endDate->format(
                'Y-m-d'
            ) .
            '.xlsx';

        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        return Excel::download(
            new CompanyEnrollmentsExport(
                company: $company,
                startDate: $startDate,
                endDate: $endDate
            ),
            $fileName
        );
    }
}