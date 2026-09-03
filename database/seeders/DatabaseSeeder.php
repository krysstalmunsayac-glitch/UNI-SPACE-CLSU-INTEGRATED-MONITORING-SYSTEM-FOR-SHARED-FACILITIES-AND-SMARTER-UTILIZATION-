<?php

namespace Database\Seeders;

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seedUsers = [
            ['name' => 'Admin', 'email' => 'superadmin@clsu.edu.ph', 'user_type' => 'super_admin'],
            ['name' => 'Office Admin CASS', 'email' => 'admin@clsu.edu.ph', 'user_type' => 'admin'],
            ['name' => 'Office Admin CEN', 'email' => 'admin2@clsu.edu.ph', 'user_type' => 'admin'],
            ['name' => 'Office Admin Engineering', 'email' => 'engineering@clsu.edu.ph', 'user_type' => 'admin'],
            ['name' => 'Office Admin Library', 'email' => 'library@clsu.edu.ph', 'user_type' => 'admin'],
            ['name' => 'Office Admin Sports', 'email' => 'sports@clsu.edu.ph', 'user_type' => 'admin'],
            ['name' => 'Juan Dela Cruz', 'email' => 'juan@clsu.edu.ph', 'user_type' => 'user'],
            ['name' => 'Maria Santos', 'email' => 'maria@clsu.edu.ph', 'user_type' => 'user'],
            ['name' => 'Pedro Reyes', 'email' => 'pedro@clsu.edu.ph', 'user_type' => 'user'],
            ['name' => 'Ana Garcia', 'email' => 'ana@clsu.edu.ph', 'user_type' => 'user'],
            ['name' => 'Test User', 'email' => 'test@example.com', 'user_type' => 'user'],
        ];

        foreach ($seedUsers as $userData) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'user_type' => $userData['user_type'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if ($user->trashed()) {
                $user->restore();
            }
        }

        // This is the sole source of seeded facilities and amenities.
        $this->call(ClsuFacilitySeeder::class);

        $amenityIds = Amenities::query()->orderBy('AID')->pluck('AID')->all();

        $seedEvent = Events::updateOrCreate(
            ['Event_Title' => 'University Founding Anniversary'],
            [
                'Description' => 'Annual celebration of the university\'s founding.',
                'Type_Event' => 'Institutional',
            ]
        );

        $requestUsers = User::query()
            ->where('user_type', 'user')
            ->orderBy('id')
            ->get();

        $requestFacilities = Facilities::query()
            ->orderBy('FID')
            ->get();

        $requestStatuses = ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Ended'];

        for ($index = 1; $index <= 20; $index++) {
            $status = $requestStatuses[($index - 1) % count($requestStatuses)];
            $user = $requestUsers[($index - 1) % $requestUsers->count()];
            $facility = $requestFacilities[($index - 1) % $requestFacilities->count()];
            $purpose = sprintf('Seeder Dummy Request %02d', $index);

            $eventDate = $status === 'Ended'
                ? now()->subDays($index + 2)
                : now()->addDays($index + 2);

            $dummyRequest = Requests::updateOrCreate(
                ['User_ID' => $user->id, 'Purpose' => $purpose],
                [
                    'Event_ID' => $seedEvent->EID,
                    'Facility_ID' => $facility->FID,
                    'Proposed_Date' => $eventDate->toDateString(),
                    'Proposed_End_Date' => $eventDate->toDateString(),
                    'Proposed_Start_Time' => '09:00:00',
                    'Proposed_End_Time' => '11:00:00',
                    'Status' => $status,
                    'Cancellation_Reason' => $status === 'Cancelled'
                        ? 'Dummy cancellation used for interface testing.'
                        : null,
                    'Rejection_Reason' => $status === 'Rejected'
                        ? 'Dummy rejection used for interface testing.'
                        : null,
                    'Purpose_Categories' => ['Academic activity'],
                    'Reservation_Frequency' => 'First time',
                    'Facility_Importance' => 'Important',
                    'Requirements_Fit' => 'Meets requirements',
                    'Reserve_Again_Intent' => 'Yes',
                    'Capacity' => min(25 + $index, (int) $facility->Capacity),
                ]
            );

            $dummyRequest->amenities()->syncWithoutDetaching(
                collect($amenityIds)
                    ->take(($index % count($amenityIds)) + 1)
                    ->all()
            );

            if (in_array($status, ['Approved', 'Ended'], true)) {
                $dummySchedule = Schedule::withTrashed()->updateOrCreate(
                    ['Request_ID' => $dummyRequest->RID],
                    [
                        'Date' => $eventDate->toDateString(),
                        'Start_Time' => '09:00:00',
                        'End_Time' => '11:00:00',
                        'Status' => 'Booked',
                    ]
                );

                if ($dummySchedule->trashed()) {
                    $dummySchedule->restore();
                }
            } else {
                Schedule::withTrashed()
                    ->where('Request_ID', $dummyRequest->RID)
                    ->forceDelete();
            }
        }
    }
}
