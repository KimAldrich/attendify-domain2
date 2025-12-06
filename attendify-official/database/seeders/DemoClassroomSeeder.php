<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoClassroomSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Academic Period
        $period = AcademicPeriod::firstOrCreate(
            [
                'year_start' => 2024,
                'year_end'   => 2025,
                'term'       => '1st',
            ],
            [
                'label'      => 'AY 2024-2025 • 1st Semester',
                'is_current' => true,
            ]
        );

        AcademicPeriod::where('id', '!=', $period->id)->update(['is_current' => false]);

        // 2) Instructor
        $instructor = User::firstOrCreate(
            ['email' => 'instructor.demo@attendify.test'],
            [
                'name'              => 'Demo Instructor',
                'first_name'        => 'Demo',
                'last_name'         => 'Instructor',
                'slug'              => 'demo-instructor-'.str()->lower(str()->random(6)),
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'is_teaching'       => true,
            ]
        );

        if (method_exists($instructor, 'assignRole')) {
            try {
                $instructor->assignRole('faculty');
            } catch (\Throwable $e) {}
        }

        // 3) Section (with embedded course info)
        $section = CourseSection::firstOrCreate(
            [
                'academic_period_id' => $period->id,
                'instructor_id'      => $instructor->id,
                'section_label'      => 'BSIT-3A',
                'course_code'        => 'IT 321',
            ],
            [
                'course_name'      => 'Web Systems & Technologies',
                'enrollment_limit' => 40,
                'is_archived'      => false,
            ]
        );

        $this->command?->info('Demo classroom data created:');
        $this->command?->info('  Period : '.$period->display_label);
        $this->command?->info('  Instructor login: '.$instructor->email.' / password');
        $this->command?->info('  Section: '.$section->course_code.' '.$section->course_name.' - '.$section->section_label);
    }
}

