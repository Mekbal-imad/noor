<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Grade;
use App\Models\ClassRoom;
use App\Models\Attendance;
use App\Models\MemorizationRecord;
use App\Models\TeacherAttendance;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──
        User::firstOrCreate(
            ['email' => 'admin@noor.com'],
            [
                'name'     => 'مدير النظام',
                'password' => Hash::make('password123'),
            ]
        );

        // ── Teachers ──
        $t1 = Teacher::firstOrCreate(
            ['email' => 'ahmed@noor.com'],
            [
                'name'           => 'أحمد محمد',
                'password'       => Hash::make('password123'),
                'phone'          => '0551234567',
                'gender'         => 'male',
                'specialization' => 'تحفيظ القرآن',
                'role'           => 'أساسي',
            ]
        );

        $t2 = Teacher::firstOrCreate(
            ['email' => 'khaled@noor.com'],
            [
                'name'           => 'خالد عبدالله',
                'password'       => Hash::make('password123'),
                'phone'          => '0559876543',
                'gender'         => 'male',
                'specialization' => 'تجويد القرآن',
                'role'           => 'معين',
            ]
        );

        $t3 = Teacher::firstOrCreate(
            ['email' => 'omar@noor.com'],
            [
                'name'           => 'عمر الفاروق',
                'password'       => Hash::make('password123'),
                'phone'          => '0554443322',
                'gender'         => 'male',
                'specialization' => 'السيرة النبوية',
                'role'           => 'أساسي',
            ]
        );

        // ── Grades ──
        $g1 = Grade::firstOrCreate(
            ['name' => 'الخامسة ابتدائي'],
            ['level' => 'primary', 'order' => 1, 'is_active' => true]
        );

        $g2 = Grade::firstOrCreate(
            ['name' => 'الأولى متوسط'],
            ['level' => 'middle', 'order' => 2, 'is_active' => true]
        );

        $g3 = Grade::firstOrCreate(
            ['name' => 'الثالثة ثانوي'],
            ['level' => 'high', 'order' => 3, 'is_active' => true]
        );

        // Assign teachers to grades
        $g1->teachers()->syncWithoutDetaching([$t1->id, $t2->id]);
        $g2->teachers()->syncWithoutDetaching([$t1->id, $t3->id]);
        $g3->teachers()->syncWithoutDetaching([$t2->id, $t3->id]);

        // ── Classes ──
        $c1 = ClassRoom::firstOrCreate(
            ['grade_id' => $g1->id, 'type' => 'قرآن'],
            [
                'name'        => 'حصة القرآن',
                'time_type'   => 'prayer',
                'prayer_time' => 'asr',
                'days'        => 'الأحد,الثلاثاء,الخميس',
                'is_active'   => true,
            ]
        );

        $c2 = ClassRoom::firstOrCreate(
            ['grade_id' => $g1->id, 'type' => 'سيرة'],
            [
                'name'        => 'حصة السيرة',
                'time_type'   => 'prayer',
                'prayer_time' => 'maghrib',
                'days'        => 'الاثنين,الأربعاء',
                'is_active'   => true,
            ]
        );

        $c3 = ClassRoom::firstOrCreate(
            ['grade_id' => $g2->id, 'type' => 'قرآن'],
            [
                'name'        => 'حصة القرآن',
                'time_type'   => 'prayer',
                'prayer_time' => 'asr',
                'days'        => 'الأحد,الثلاثاء,الخميس',
                'is_active'   => true,
            ]
        );

        $c4 = ClassRoom::firstOrCreate(
            ['grade_id' => $g2->id, 'type' => 'عقيدة'],
            [
                'name'        => 'حصة العقيدة',
                'time_type'   => 'prayer',
                'prayer_time' => 'maghrib',
                'days'        => 'الاثنين,الأربعاء',
                'is_active'   => true,
            ]
        );

        $c5 = ClassRoom::firstOrCreate(
            ['grade_id' => $g3->id, 'type' => 'قرآن'],
            [
                'name'        => 'حصة القرآن',
                'time_type'   => 'prayer',
                'prayer_time' => 'asr',
                'days'        => 'الأحد,الثلاثاء,الخميس',
                'is_active'   => true,
            ]
        );

        // Assign teachers to classes
        $c1->teachers()->syncWithoutDetaching([$t1->id, $t2->id]);
        $c2->teachers()->syncWithoutDetaching([$t1->id]);
        $c3->teachers()->syncWithoutDetaching([$t1->id, $t3->id]);
        $c4->teachers()->syncWithoutDetaching([$t3->id]);
        $c5->teachers()->syncWithoutDetaching([$t2->id, $t3->id]);

        // ── Parents ──
        $p1 = ParentModel::firstOrCreate(
            ['email' => 'parent1@noor.com'],
            [
                'name'     => 'محمد أحمد العمري',
                'password' => Hash::make('noor1234'),
                'phone'    => '0551111111',
            ]
        );

        $p2 = ParentModel::firstOrCreate(
            ['email' => 'parent2@noor.com'],
            [
                'name'     => 'عبدالله سالم',
                'password' => Hash::make('noor1234'),
                'phone'    => '0552222222',
            ]
        );

        $p3 = ParentModel::firstOrCreate(
            ['email' => 'parent3@noor.com'],
            [
                'name'     => 'يوسف إبراهيم',
                'password' => Hash::make('noor1234'),
                'phone'    => '0553333333',
            ]
        );

        // ── Students ──
        $s1 = Student::firstOrCreate(
            ['name' => 'عبدالرحمن محمد', 'parent_id' => $p1->id],
            ['grade_id' => $g1->id, 'gender' => 'male', 'grade_level' => 'primary_5', 'status' => 'approved']
        );

        $s2 = Student::firstOrCreate(
            ['name' => 'أنس سالم', 'parent_id' => $p2->id],
            ['grade_id' => $g1->id, 'gender' => 'male', 'grade_level' => 'primary_5', 'status' => 'approved']
        );

        $s3 = Student::firstOrCreate(
            ['name' => 'يوسف إبراهيم', 'parent_id' => $p3->id],
            ['grade_id' => $g1->id, 'gender' => 'male', 'grade_level' => 'primary_5', 'status' => 'approved']
        );

        $s4 = Student::firstOrCreate(
            ['name' => 'سعد محمد', 'parent_id' => $p1->id],
            ['grade_id' => $g2->id, 'gender' => 'male', 'grade_level' => 'middle_1', 'status' => 'approved']
        );

        $s5 = Student::firstOrCreate(
            ['name' => 'فهد سالم', 'parent_id' => $p2->id],
            ['grade_id' => $g2->id, 'gender' => 'male', 'grade_level' => 'middle_1', 'status' => 'approved']
        );

        $s6 = Student::firstOrCreate(
            ['name' => 'خالد يوسف', 'parent_id' => $p3->id],
            ['grade_id' => $g3->id, 'gender' => 'male', 'grade_level' => 'high_3', 'status' => 'approved']
        );

        // Enroll students in classes
        $c1->students()->syncWithoutDetaching([$s1->id, $s2->id, $s3->id]);
        $c2->students()->syncWithoutDetaching([$s1->id, $s2->id, $s3->id]);
        $c3->students()->syncWithoutDetaching([$s4->id, $s5->id]);
        $c4->students()->syncWithoutDetaching([$s4->id, $s5->id]);
        $c5->students()->syncWithoutDetaching([$s6->id]);

        // ── Attendance Records ──
        $dates    = ['2026-07-01', '2026-07-02', '2026-07-03', '2026-07-06'];
        $statuses = ['present', 'present', 'present', 'absent', 'late'];

        foreach ($dates as $date) {
            foreach ([$s1->id, $s2->id, $s3->id] as $sid) {
                Attendance::firstOrCreate(
                    ['student_id' => $sid, 'class_id' => $c1->id, 'date' => $date],
                    ['status' => $statuses[array_rand($statuses)]]
                );
            }
            foreach ([$s4->id, $s5->id] as $sid) {
                Attendance::firstOrCreate(
                    ['student_id' => $sid, 'class_id' => $c3->id, 'date' => $date],
                    ['status' => $statuses[array_rand($statuses)]]
                );
            }
        }

        // ── Teacher Attendance ──
        foreach ($dates as $date) {
            TeacherAttendance::firstOrCreate(
                ['class_id' => $c1->id, 'teacher_id' => $t1->id, 'date' => $date],
                ['status' => 'present', 'class_held' => true]
            );
            TeacherAttendance::firstOrCreate(
                ['class_id' => $c3->id, 'teacher_id' => $t1->id, 'date' => $date],
                ['status' => 'present', 'class_held' => true]
            );
        }

        // ── Memorization Records ──
        foreach ([$s1->id, $s2->id, $s3->id] as $sid) {
            MemorizationRecord::firstOrCreate(
                ['student_id' => $sid, 'teacher_id' => $t1->id, 'date' => '2026-07-01'],
                [
                    'type'       => 'memorization',
                    'from_surah' => 'الفاتحة',
                    'from_ayah'  => 1,
                    'to_surah'   => 'الفاتحة',
                    'to_ayah'    => 7,
                    'grade'      => rand(7, 10),
                ]
            );

            MemorizationRecord::firstOrCreate(
                ['student_id' => $sid, 'teacher_id' => $t1->id, 'date' => '2026-07-02'],
                [
                    'type'       => 'revision',
                    'from_surah' => 'البقرة',
                    'from_ayah'  => 1,
                    'to_surah'   => 'البقرة',
                    'to_ayah'    => 10,
                    'grade'      => rand(6, 10),
                ]
            );
        }

        $this->command->info('✅ تم إنشاء البيانات التجريبية بنجاح!');
    }
}