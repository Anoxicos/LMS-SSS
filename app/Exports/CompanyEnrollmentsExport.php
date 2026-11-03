<?php

namespace App\Exports;

use App\Exports\Sheets\CompanyEnrollmentMonthSheet;
use App\Models\Company;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CompanyEnrollmentsExport implements WithMultipleSheets
{
    public function __construct(
        private Company $company,
        private Carbon $startDate,
        private Carbon $endDate
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        $currentMonth = $this->startDate
            ->copy()
            ->startOfMonth();

        $lastMonth = $this->endDate
            ->copy()
            ->startOfMonth();

        while ($currentMonth->lte($lastMonth)) {

            /*
            |--------------------------------------------------------------------------
            | Start date for this sheet
            |--------------------------------------------------------------------------
            */

            $sheetStart = $currentMonth
                ->copy()
                ->startOfMonth();

            if ($sheetStart->lt($this->startDate)) {
                $sheetStart =
                    $this->startDate->copy();
            }

            /*
            |--------------------------------------------------------------------------
            | End date for this sheet
            |--------------------------------------------------------------------------
            */

            $sheetEnd = $currentMonth
                ->copy()
                ->endOfMonth();

            if ($sheetEnd->gt($this->endDate)) {
                $sheetEnd =
                    $this->endDate->copy();
            }

            $sheets[] =
                new CompanyEnrollmentMonthSheet(
                    company: $this->company,
                    startDate: $sheetStart,
                    endDate: $sheetEnd,
                    month: $currentMonth->copy()
                );

            $currentMonth->addMonth();
        }

        return $sheets;
    }
}