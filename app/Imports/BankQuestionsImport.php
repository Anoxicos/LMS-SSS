<?php

namespace App\Imports;

use App\Models\Bank;
use App\Models\Question;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankQuestionsImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        protected Bank $bank
    ) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $nameChapter = trim((string) ($row['chapter'] ?? ''));
            $questionText = trim((string) ($row['question'] ?? ''));
            $response1 = trim((string) ($row['response1'] ?? ''));
            $response2 = trim((string) ($row['response2'] ?? ''));
            $response3 = trim((string) ($row['response3'] ?? ''));
            $correctAnswer = trim((string) ($row['correct_answer'] ?? ''));

            if (
                empty($questionText) ||
                empty($response1) ||
                empty($response2) ||
                empty($response3) ||
                empty($correctAnswer)
            ) {
                continue;
            }

            if (!in_array($correctAnswer, [$response1, $response2, $response3], true)) {
                continue;
            }

            Question::create([
                'bank_id' => $this->bank->id,
                'name_chapter' => $nameChapter ?: null,
                'question' => $questionText,
                'response1' => $response1,
                'response2' => $response2,
                'response3' => $response3,
                'correct_answer' => $correctAnswer,
                'is_modified' => false,
            ]);
        }
    }
}