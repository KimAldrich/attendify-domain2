<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $questions = [
            // 5 Likert questions (scope: both event and activity)
            [
                'label'      => 'The overall organization of the event/activity was satisfactory.',
                'scope_type' => 'both',
                'scale_type' => 'likert_1_5',
                'category'   => 'organization',
            ],
            [
                'label'      => 'The content of the event/activity was relevant to my needs.',
                'scope_type' => 'both',
                'scale_type' => 'likert_1_5',
                'category'   => 'content',
            ],
            [
                'label'      => 'The speakers/facilitators were effective and engaging.',
                'scope_type' => 'both',
                'scale_type' => 'likert_1_5',
                'category'   => 'speakers',
            ],
            [
                'label'      => 'The venue and logistics (schedule, environment, platform) were adequate.',
                'scope_type' => 'both',
                'scale_type' => 'likert_1_5',
                'category'   => 'logistics',
            ],
            [
                'label'      => 'I would be willing to join similar events/activities in the future.',
                'scope_type' => 'both',
                'scale_type' => 'likert_1_5',
                'category'   => 'future_interest',
            ],

            // 3 open-ended comment questions
            [
                'label'      => 'What did you like most about this event/activity?',
                'scope_type' => 'both',
                'scale_type' => 'text',
                'category'   => 'likes',
            ],
            [
                'label'      => 'What did you like least about this event/activity?',
                'scope_type' => 'both',
                'scale_type' => 'text',
                'category'   => 'dislikes',
            ],
            [
                'label'      => 'What are your recommendations and suggestions for improvement?',
                'scope_type' => 'both',
                'scale_type' => 'text',
                'category'   => 'suggestions',
            ],
        ];

        foreach ($questions as $q) {
            DB::table('evaluation_questions')->updateOrInsert(
                [
                    'label'      => $q['label'],
                ],
                [
                    'scope_type' => $q['scope_type'],
                    'scale_type' => $q['scale_type'],
                    'category'   => $q['category'],
                    'is_active'  => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
