<?php

namespace App\Exports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BankQuestionsExport implements FromArray, ShouldAutoSize
{
    public function __construct(
        protected Bank $bank
    ) {}

    public function array(): array
    {
        $rows = [
            [
                'chapter',
                'question',
                'response1',
                'response2',
                'response3',
                'correct_answer',
            ],
        ];

        $this->bank->load([
            'questions' => function ($query) {
                $query->latest();
            },
        ]);

        foreach ($this->bank->questions as $question) {
            $rows[] = [
                $question->name_chapter,
                $question->question,
                $question->response1,
                $question->response2,
                $question->response3,
                $question->correct_answer,
            ];
        }

        return $rows;
    }
}