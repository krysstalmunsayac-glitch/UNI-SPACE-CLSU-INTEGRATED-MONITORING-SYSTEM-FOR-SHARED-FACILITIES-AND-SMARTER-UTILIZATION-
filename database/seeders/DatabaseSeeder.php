<?php

namespace Database\Seeders;

use App\Models\Amenities;
use App\Models\Events;
use App\Models\Facilities;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $seedUsers = [
            [
                'name' => 'Admin',
                'email' => 'superadmin@clsu.edu.ph',
                'user_type' => 'super_admin',
            ],
            [
                'name' => 'Office Admin CASS',
                'email' => 'admin@clsu.edu.ph',
                'user_type' => 'admin',
            ],
            [
                'name' => 'Office Admin CEN',
                'email' => 'admin2@clsu.edu.ph',
                'user_type' => 'admin',
            ],
            [
                'name' => 'Office Admin Engineering',
                'email' => 'engineering@clsu.edu.ph',
                'user_type' => 'admin',
            ],
            [
                'name' => 'Office Admin Library',
                'email' => 'library@clsu.edu.ph',
                'user_type' => 'admin',
            ],
            [
                'name' => 'Office Admin Sports',
                'email' => 'sports@clsu.edu.ph',
                'user_type' => 'admin',
            ],
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@clsu.edu.ph',
                'user_type' => 'user',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@clsu.edu.ph',
                'user_type' => 'user',
            ],
            [
                'name' => 'Pedro Reyes',
                'email' => 'pedro@clsu.edu.ph',
                'user_type' => 'user',
            ],
            [
                'name' => 'Ana Garcia',
                'email' => 'ana@clsu.edu.ph',
                'user_type' => 'user',
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'user_type' => 'user',
            ],
        ];

        foreach ($seedUsers as $userData) {
            User::updateOrCreate(
                [
                    'email' => $userData['email'],
                ],
                [
                    'name' => $userData['name'],
                    'user_type' => $userData['user_type'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Facilities
        |--------------------------------------------------------------------------
        */

        $facilities = [
            [
                'Facility_Name' => 'CLSU Convention Center',
                'facility_type' => 'conference',
                'Price' => 5000,
                'Office' => 'Office of Student Affairs',
                'Description' => 'Main convention center for university-wide events.',
                'Location' => 'CLSU Main Campus',
                'Capacity' => 500,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'CASS Conference Room',
                'facility_type' => 'conference',
                'Price' => 2500,
                'Office' => 'College of Arts and Social Sciences',
                'Description' => 'Conference room for meetings and seminars.',
                'Location' => 'CASS Building',
                'Capacity' => 120,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'CEN AVR',
                'facility_type' => 'conference',
                'Price' => 3000,
                'Office' => 'College of Engineering',
                'Description' => 'Audio Visual Room for presentations and meetings.',
                'Location' => 'Engineering Building',
                'Capacity' => 150,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'University Gymnasium',
                'facility_type' => 'sports',
                'Price' => 7000,
                'Office' => 'Sports Office',
                'Description' => 'Indoor gymnasium for sports and university events.',
                'Location' => 'Sports Complex',
                'Capacity' => 1000,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'CLSU Auditorium',
                'facility_type' => 'auditorium',
                'Price' => 6000,
                'Office' => 'Administration',
                'Description' => 'Auditorium for graduations, ceremonies, and programs.',
                'Location' => 'Administration Building',
                'Capacity' => 800,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'Library Function Hall',
                'facility_type' => 'conference',
                'Price' => 3500,
                'Office' => 'University Library',
                'Description' => 'Function hall located inside the university library.',
                'Location' => 'University Library',
                'Capacity' => 200,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'ICT Training Room',
                'facility_type' => 'laboratory',
                'Price' => 1500,
                'Office' => 'Management Information Systems Office',
                'Description' => 'Computer laboratory used for training activities.',
                'Location' => 'MIS Office',
                'Capacity' => 40,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'CEA Lecture Hall',
                'facility_type' => 'classroom',
                'Price' => 1800,
                'Office' => 'College of Engineering',
                'Description' => 'Large lecture room for academic activities.',
                'Location' => 'CEA Building',
                'Capacity' => 180,
                'Status' => 'Available',
            ],
            [
                'Facility_Name' => 'Agriculture Seminar Hall',
                'facility_type' => 'conference',
                'Price' => 2800,
                'Office' => 'College of Agriculture',
                'Description' => 'Seminar hall for academic and institutional events.',
                'Location' => 'Agriculture Building',
                'Capacity' => 150,
                'Status' => 'Available',
            ],
        ];

        foreach ($facilities as $facilityData) {
            Facilities::updateOrCreate(
                [
                    'Facility_Name' => $facilityData['Facility_Name'],
                ],
                $facilityData
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Amenities
        |--------------------------------------------------------------------------
        */

        $projector = Amenities::updateOrCreate(
            [
                'name' => 'Projector',
            ],
            [
                'Description' => 'LCD projector for presentations.',
                'Status' => 'Available',
            ]
        );

        $soundSystem = Amenities::updateOrCreate(
            [
                'name' => 'Sound System',
            ],
            [
                'Description' => 'PA sound system for programs and events.',
                'Status' => 'Available',
                'reservation_limit' => 5,
            ]
        );

        $wifi = Amenities::updateOrCreate(
            [
                'name' => 'Wi-Fi',
            ],
            [
                'Description' => 'High-speed wireless internet connection.',
                'Status' => 'Available',
            ]
        );

        $airConditioning = Amenities::updateOrCreate(
            [
                'name' => 'Air Conditioning',
            ],
            [
                'Description' => 'Fully air-conditioned venue.',
                'Status' => 'Available',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Attach Amenities to Facilities
        |--------------------------------------------------------------------------
        */

        $amenityIds = [
            $projector->AID,
            $soundSystem->AID,
            $wifi->AID,
            $airConditioning->AID,
        ];

        Facilities::query()
            ->each(function (Facilities $facility) use ($amenityIds): void {
                $facility->amenities()->syncWithoutDetaching($amenityIds);
            });

        /*
        |--------------------------------------------------------------------------
        | Events
        |--------------------------------------------------------------------------
        */

        Events::updateOrCreate(
            [
                'Event_Title' => 'University Founding Anniversary',
            ],
            [
                'Description' => 'Annual celebration of the university\'s founding.',
                'Type_Event' => 'Institutional',
                'Status' => 'Upcoming',
            ]
        );
    }
}
