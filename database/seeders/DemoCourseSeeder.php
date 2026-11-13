<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo courses can only be seeded in a local or testing environment.');
        }

        $courses = [
            ['DEMO-SMS-01', 'Safety management systems', 'Aviation safety', 'Explore how safety reporting, risk assessment, and follow-up fit together in an aviation organization.', 'Safety management', 4, 'beginner', [
                ['Safety reporting', 'A safety report records an event or hazard so the organization can review it. A useful report describes what happened, where it happened, and the conditions at the time.'],
                ['From hazard to action', 'A risk assessment considers the possible outcome of a hazard and the likelihood of that outcome. Teams record their assessment and review whether the actions taken address the risk.'],
            ]],
            ['DEMO-HF-01', 'Human factors in aviation maintenance', 'Maintenance', 'Review how communication, fatigue, and working conditions can affect maintenance decisions.', 'Human factors', 3, 'beginner', [
                ['Communication at handover', 'A clear handover identifies work completed, work still open, and any change in aircraft or equipment status. The receiving person should have an opportunity to ask questions.'],
                ['Fatigue and attention', 'Fatigue can affect attention and judgment. Follow your organization’s fatigue reporting process when you are concerned about your ability to work safely.'],
            ]],
            ['DEMO-EWIS-01', 'Electrical wiring awareness', 'Maintenance', 'An introduction to wiring terminology, inspection awareness, and the importance of approved maintenance data.', 'EWIS', 5, 'intermediate', [
                ['Wiring systems', 'Electrical wiring interconnection systems include wiring, connectors, and related components. Their condition can be affected by installation, environment, and maintenance activity.'],
                ['Inspection and reporting', 'Record and report suspected damage using your organization’s procedures. Use the current approved maintenance data for inspection and repair instructions.'],
            ]],
            ['DEMO-DGR-01', 'Dangerous goods awareness', 'Ground operations', 'Recognize common dangerous goods concerns and understand when to refer an item for specialist review.', 'Dangerous goods', 2, 'beginner', [
                ['Recognizing a concern', 'Labels, package markings, and shipping documents can indicate that an item needs additional handling. When information is unclear, ask the responsible dangerous goods specialist.'],
                ['Escalating an issue', 'Do not accept or handle a suspect item based on a summary lesson. Follow the current operator procedures and applicable regulations, and report discrepancies through the proper channel.'],
            ]],
        ];

        DB::transaction(function () use ($courses) {
            foreach ($courses as [$ref, $title, $categoryName, $description, $tagName, $hours, $level, $lessons]) {
                $category = Category::firstOrCreate(['name' => $categoryName], ['is_active' => true]);
                $course = Course::firstOrCreate(['ref' => $ref], [
                    'category_id' => $category->id,
                    'title' => $title,
                    'description' => 'Demo course: '.$description,
                    'type' => 'free',
                    'training_type' => 'online',
                    'method' => 'Online e-learning (LMS)',
                    'duration_hours' => $hours,
                    'passing_score' => 80,
                    'skill_level' => $level,
                    'course_focus' => $tagName,
                    'language' => 'English',
                    'price' => 0,
                    'currency' => 'MAD',
                    'is_published' => true,
                    'content_type' => 'normal',
                ]);

                if (! $course->wasRecentlyCreated) {
                    continue;
                }

                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $course->tags()->attach($tag);
                $chapter = $course->chapters()->create([
                    'title' => 'Course overview',
                    'description' => 'Sample lessons for demonstration. This course is not operational training.',
                    'sort_order' => 1,
                ]);

                foreach ($lessons as $index => [$lessonTitle, $content]) {
                    $chapter->slides()->create([
                        'title' => $lessonTitle,
                        'lesson_type' => 'text',
                        'content' => '<p>'.e($content).'</p>',
                        'sort_order' => $index + 1,
                        'is_preview' => $index === 0,
                    ]);
                }
            }
        });
    }
}
