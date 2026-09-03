<?php

namespace Database\Seeders;

use App\Models\Amenities;
use App\Models\Facilities;
use Illuminate\Database\Seeder;

class ClsuFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $standardProtocols = 'Observe proper waste disposal. Weapons, drugs, and items that can harm a person are prohibited.';
        $noFoodProtocols = 'No food or beverages allowed. '.$standardProtocols;

        $facilities = [
            $this->facility('CEd Teachers’ Hall (College of Education)', 'auditorium', 'Shared', 10000, '₱10,000 for 8 hours.', 'College of Education', 'Multi-purpose amphitheater-style hall for examinations, education summits, faculty forums, lectures, and institutional activities.', 230, 'Available', ['Projector', 'Projection Screen', 'Sound System', 'Air Conditioning', 'Internet Connection'], $noFoodProtocols, 'Dr. Florante P. Ibarra, CEd', 'https://drive.google.com/drive/folders/15GjZBl5y4pBNx4F-wRPywMjFI8AZVnNX?usp=sharing', 'No official guidelines yet.'),
            $this->facility('University Auditorium', 'auditorium', 'Shared', 10000, '₱10,000 for 4 hours (day rate); ₱1,750 for each succeeding hour.', 'Strategic Communication Office', 'Large assembly and performance venue for ceremonies, conferences, lectures, institutional programs, and cultural shows.', 1900, 'Unavailable', ['Built-in LED Wall', 'Sound System', 'Internet Connection'], $noFoodProtocols, 'Assoc. Prof. Maria Adrielle S. Estigoy, StratCom', 'https://drive.google.com/drive/folders/1sNMp900aP8XkfULysFBzFp-vQjvJMKYR?usp=sharing', 'Under renovation; no official guidelines yet.'),
            $this->facility('OVPAA Amphitheater', 'auditorium', 'Shared', 0, 'No rental fees.', 'Office of the Vice President for Academic Affairs', 'Air-conditioned venue for benchmarking, awarding ceremonies, academic forums, lectures, training, exhibits, and collaborative programs.', 70, 'Available', ['Built-in LED Wall', 'Sound System', 'Internet Connection', 'Air Conditioning'], $noFoodProtocols, 'Dr. Ravelina R. Velasco, OVPAA', 'https://drive.google.com/drive/folders/1QBnS-D4i0lQm6DBr9DQcQ22uSAvg6Y5b?usp=sharing'),
            $this->facility('RIE Amphitheater', 'auditorium', 'Shared', 12500, '₱12,500 for 8 hours; ₱937.50 for each succeeding hour; external non-refundable reservation fee: ₱2,500.', 'University Extension Program Office', 'Venue for research, innovation, extension, training, workshops, congresses, pitch events, and development-oriented gatherings.', 150, 'Available', ['Built-in LED Wall', 'Sound System', 'Air Conditioning', 'Internet Connection'], $noFoodProtocols, 'Dr. Aldrin Badua, UEPO', 'https://drive.google.com/drive/folders/1L7KKzywM9dpjdCYBqPl2axEFVA5r0YJZ?usp=sharing', 'No official guidelines yet.'),
            $this->facility('RIE Training Function Room B', 'conference', 'Shared', 5000, '₱5,000 for 8 hours; ₱312.50 for each succeeding hour.', 'University Extension Program Office', 'Training function room; the source document does not yet provide a full description.', 25, 'Available', ['Television', 'Sound System', 'Air Conditioning', 'Internet Connection'], $standardProtocols, 'Dr. Aldrin Badua, UEPO', 'https://drive.google.com/drive/folders/1kuJMPvxvlxIEvOzxPbS7aP8mJpZPF3ot?usp=sharing', 'Old rates; new rates are subject to approval under the Manual of Operations. No official guidelines yet.'),
            $this->facility('FPJ Communication Hall', 'conference', 'Shared', 10000, 'CLSU events are free, with utility fees after 5:00 PM. Non-CLSU events: ₱10,000 for 10 hours including ingress and egress. Succeeding/overtime: ₱1,000 per hour for the hall and ₱85 per hour/person for utilities. Weekend utility fee: ₱1,950.', 'College of Arts and Social Sciences', 'Flexible hall for seminars, conferences, training, celebrations, and special occasions; accommodates 600 in seminar setup or 300 with tables and chairs.', 600, 'Unavailable', ['Air Conditioning'], $standardProtocols.' Submit a request letter addressed to the University President through the Office of the President; approval precedes payment and agreement signing.', 'Dr. Andrea May C. Malonzo, CASS', 'https://drive.google.com/drive/folders/1KgJ8m7sj9NhLseClvig6IZJkPonn7OX_?usp=sharing', 'Under renovation.'),
            $this->facility('RIE Conference Hall', 'conference', 'Shared', 8000, '₱8,000 for 8 hours; ₱625 for each succeeding hour.', 'University Extension Program Office', 'Conference and meeting venue supporting research, presentations, workshops, consultations, benchmarking, and training.', 60, 'Available', ['Projector', 'Projection Screen', 'Sound System', 'Internet Connection', 'Air Conditioning'], $standardProtocols, 'Dr. Aldrin Badua, UEPO', 'https://drive.google.com/drive/folders/14lgZRPKwYLabjauzcZ-KxXqI4pg2_fNW?usp=sharing', 'Old rates; new rates are subject to approval under the Manual of Operations. No official guidelines yet.'),
            $this->facility('UEPO Farmer’s Hall', 'conference', 'Shared', 8000, '₱8,000 for 8 hours.', 'University Extension Program Office', 'Venue for workshops, training, seminars, and stakeholder consultations supporting agricultural learning and dialogue.', 60, 'Available', ['Projector', 'Projection Screen', 'Sound System', 'Internet Connection', 'Air Conditioning'], $standardProtocols, 'Dr. Aldrin Badua, UEPO', 'https://drive.google.com/drive/folders/1PEPg59noCKnlkMd5S_aJivBWpDGI5cQB?usp=sharing', 'Old rates; new rates are subject to approval under the Manual of Operations. No official guidelines yet.'),
            $this->facility('RC Undan Hall', 'conference', 'Shared', 10000, '₱10,000 on weekdays; ₱11,000 on weekends.', 'Center for Hybrid Rice Research, Innovation, and Mechanization', 'Training and meeting venue for hybrid rice technology, seed production, breeding, mechanization, demonstrations, and extension activities.', 120, 'Available', ['Projector', 'Sound System', 'Internet Connection', 'Air Conditioning'], $noFoodProtocols, 'Dr. Marvin M. Cinense, Director', 'https://drive.google.com/drive/folders/1l2K_Pw6L8qzFW0s_YAeHoXphwb8r0GfP?usp=sharing'),
            $this->facility('RM-CARES Organic Farming Training Facility', 'conference', 'Shared', 5000, '₱5,000 for 8 hours.', 'Ramon Magsaysay Center for Agricultural Resources and Environmental Studies', 'Training complex for sustainable and organic farming, vegetable production, and environmentally friendly agricultural technologies.', 30, 'Available', ['Projector', 'Projection Screen', 'Sound System', 'Internet Connection', 'Air Conditioning'], $noFoodProtocols, 'Dr. Rubigilda P. Alili, RM-CARES', 'https://drive.google.com/drive/folders/1Yn_ftQstlCRhy0Q3w8_97WaBDvlK_6AN?usp=sharing', 'Source capacity is 25–30.'),
            $this->facility('RM-CARES Conference Room', 'conference', 'Shared', 0, 'No rental fees.', 'Ramon Magsaysay Center for Agricultural Resources and Environmental Studies', 'Professional air-conditioned room for lectures, seminars, meetings, and presentations.', 35, 'Available', ['Air Conditioning', 'Tables and Chairs', 'Television', 'Sound System'], 'No smoking or vandalism; keep noise low.', null, null),
            $this->facility('Agro-Biological Research Laboratory Conference Room', 'laboratory', 'Exclusive', 0, 'No rental fees; for CLSU students or staff only.', 'Agro-Biological Research Laboratory', 'Research meeting room for seminars, workshops, project presentations, thesis defenses, and technical discussions.', 35, 'Available', ['Projector', 'Sound System', 'Projection Screen', 'Internet Connection', 'Air Conditioning'], $noFoodProtocols.' Observe biosafety, biosecurity, and waste-management regulations. Visitors require prior approval, escort, and must remain in the hall. Operating hours: 8:00 AM–5:00 PM.', 'Asst. Prof. Paula Blanca Aquino · aquinopb@clsu.edu.ph', 'https://drive.google.com/drive/folders/1mPR9ocDB05ZsCzQX6dmB5MAX3WGyWepo?usp=sharing'),
            $this->facility('Reimers Hall', 'auditorium', 'Exclusive', 0, 'Free for small groups and short events, subject to director approval.', 'Center for Central Luzon Studies', 'Performance and seating venue for cultural presentations, ceremonies, lectures, conferences, seminars, and social gatherings.', 100, 'Available', ['Stage', 'Microphone', 'Projector', 'Air Conditioning', 'Internet Connection'], $standardProtocols.' Use is after office hours and subject to approval.', 'Dr. Melanie Tolentino, Director · clsu_ccls@clsu.edu.ph · 09996707169', 'https://drive.google.com/drive/folders/1_04Kh-2ZAuAbOcdxuGera0xYmJ5-cOiV?usp=sharing', 'Guidelines and protocols from the Manual of Operations are not yet approved.'),
            $this->facility('Multi-purpose Gym (MPG)', 'sports', 'Shared', 4000, 'Outsider day rate: ₱4,000 for 4 hours, then ₱1,000/hour. Night rate: ₱5,000 for 4 hours, then ₱1,250/hour.', 'OVPAD General Services Division', 'Spacious indoor venue for sports, recreation, seminars, conferences, exhibitions, ceremonies, and large-group activities.', 2000, 'Available', ['Stage', 'Large Ceiling Fans'], $standardProtocols.' A completed request form is required.', 'Engr. Ronnie De Guzman, OVPAD-General Services Division', 'https://drive.google.com/drive/folders/1pemydjMhnGU1la1_eCzLT-We9TLEM334?usp=sharing'),
            $this->facility('Sports Development Office / Gymnatorium', 'sports', 'Shared', 1700, 'Venue: ₱1,700/hour; air-conditioning for 8 hours: ₱12,000.', 'Sports Development Office', 'Multipurpose indoor sports facility for PE classes, athletics, intramurals, competitions, and sports programs.', 2000, 'Available', ['Sports Facilities', 'Basketball Court', 'Open Space', 'Internet Connection', 'Air Conditioning'], $standardProtocols.' The court must be covered for non-sports events.', 'Assoc. Prof. John A. Agaton, Head, SDO', 'https://drive.google.com/drive/folders/1PtrkfTgdazy6Kv6nfKHxpB9w4UDd6GcP?usp=sharing'),
            $this->facility('Oval Ground / Grandstand', 'sports', 'Shared', null, 'Fee depends on the activity.', 'Sports Development Office', 'Open field and grandstand for University Week, intramurals, track and field, baseball, ceremonies, recreation, and exhibitions.', 6000, 'Available', ['Stage', 'Open Space', 'Track and Field', 'Internet Connection'], $standardProtocols, 'Assoc. Prof. John A. Agaton, Head, SDO', 'https://drive.google.com/drive/folders/1FFwEJckc8An3jyPWY7hBrqMXcJiFA8Fl?usp=sharing'),
            $this->facility('CASS Little Theater', 'auditorium', 'Shared', null, 'To follow.', 'College of Arts and Social Sciences', 'Performance and presentation venue for theater, lectures, seminars, workshops, auditions, and film screenings.', null, 'Unavailable', [], $noFoodProtocols, 'Dr. Jay C. Santos, CASS Dean', 'https://drive.google.com/drive/folders/1NDRepO6zdqoVNx9hYYWDX51hQSkttQYU?usp=sharing', 'Under renovation; capacity, rate, and amenities are to follow.'),
            $this->facility('CBA Mini Theater', 'auditorium', 'Shared', 0, 'No rental fees.', 'College of Business and Accountancy', 'Academic venue for classes, seminars, meetings, workshops, and orientations.', 80, 'Unavailable', [], $noFoodProtocols.' A reservation form must be processed through the College Registrar’s Office before use.', 'Dr. Edilyn V. Linsangan, CBA Dean', 'https://drive.google.com/drive/folders/1PG5_IeoDR2UlWQOVxzvs2dq-1_OgQd3s?usp=sharing', 'Under renovation; source capacity is 60–80 and amenities are to follow.'),
            $this->facility('CenTrAD Amphitheater', 'auditorium', 'Exclusive', 0, 'No rental fees.', 'Center for Transboundary Animal Diseases', 'Specialized venue for technical training, research presentations, knowledge-sharing, and animal-health programs.', 50, 'Available', ['Projector', 'Projection Screen', 'Air Conditioning', 'Sound System', 'Internet Connection'], $standardProtocols.' Mainly for research presentations, 8:00 AM–5:00 PM, and CLSU students/staff. Visitors must remain in the hall.', 'Dr. Virginia M. Venturina, CenTrAD', 'https://drive.google.com/drive/folders/1iBVg-C03vOWrY4S3hfEFNbq3Hzj-7f5-?usp=sharing'),
            $this->facility('Old Admin Conference Room', 'conference', 'Exclusive', 0, 'No rental fees.', 'Office of the University President', 'Formal room for administrative meetings, benchmarking, planning, presentations, committee meetings, and MOA signing.', 30, 'Available', ['Air Conditioning', 'Projector', 'Speaker', 'Microphone', 'Internet Connection'], $noFoodProtocols, 'University President Evaristo A. Abella · op@clsu.edu.ph', 'https://drive.google.com/drive/folders/1sQ7oEz6u129hEdwajmEHZtXPNjJeVO9m?usp=sharing'),
            $this->facility('New Admin Conference Room', 'conference', 'Exclusive', 0, 'No rental fees.', 'Office of the University President', 'Formal room for administrative meetings, benchmarking, planning, presentations, committee meetings, and MOA signing.', 30, 'Available', ['Air Conditioning', 'Projector', 'Speaker', 'Microphone', 'Internet Connection'], $noFoodProtocols, 'University President Evaristo A. Abella · op@clsu.edu.ph', 'https://drive.google.com/drive/folders/1sQ7oEz6u129hEdwajmEHZtXPNjJeVO9m?usp=sharing'),
            $this->facility('Silid-Likhaan (Dungon Museum)', 'conference', 'Shared', 0, 'No rental fees.', 'Strategic Communication Office', 'Quiet, culturally rich museum space for meetings, discussions, workshops, and stakeholder activities.', 20, 'Available', ['Tables and Chairs', 'Internet Connection', 'Projector', 'Air Conditioning', 'Couch'], $noFoodProtocols, 'Assoc. Prof. Maria Adrielle S. Estigoy, StratCom', 'https://drive.google.com/drive/folders/1cuQ79QJ7Ar61DNO8eOs3QdRcVkapfmwe?usp=sharing'),
            $this->facility('Dungon Museum Hall', 'other', 'Shared', 0, 'No rental fees.', 'Strategic Communication Office', 'Historically themed open space for art exhibits, training, student activities, performances, and programs.', 30, 'Available', ['Open Space', 'Internet Connection', 'Projector', 'Microphone', 'Speaker', 'Air Conditioning'], $noFoodProtocols, 'Assoc. Prof. Maria Adrielle S. Estigoy, StratCom', 'https://drive.google.com/drive/folders/1cuQ79QJ7Ar61DNO8eOs3QdRcVkapfmwe?usp=sharing', 'Source capacity is 20–30.'),
            $this->facility('CLIRDEC Conference Room', 'conference', 'Shared', null, 'To follow.', 'CLSU ICT Research and Development Training Center', 'Connected conference room for online meetings, quality-assurance activities, institutional events, and supported student organizations.', 170, 'Available', ['Television', 'Sound System', 'Microphone', 'Internet Connection', 'Air Conditioning'], $noFoodProtocols, 'Dr. Anjela C. Tolentino, CLSU-DIT', 'https://drive.google.com/drive/folders/1aUAthGfXTYdlKqNbMZ09bgrB9dGBFrKj?usp=sharing', 'Capacity is 150–170 with chairs or 50–70 with tables; rate is to follow.'),
            $this->facility('CCC Amphitheatre', 'auditorium', 'Shared', 5000, '₱5,000; rate is not yet approved.', 'College/Center for Environmental Studies', 'Amphitheater for seminars, workshops, training, culminating activities, proposal writing, immersion, and environmental education.', 72, 'Available', ['Stage', 'Sound System', 'Microphone', 'Projector', 'Projection Screen', 'Internet Connection', 'Restroom Access'], $noFoodProtocols, 'Assoc. Prof. Roberto D. Pelayo, CCC', 'https://drive.google.com/drive/folders/1sBAWmcaRDmRRwvLG8vxAnjAFYKCHIrt5?usp=sharing', 'Rate is not yet approved; internet is subject to availability.'),
            $this->facility('CCCEM Training Room', 'conference', 'Shared', 5000, '₱5,000; rate is not yet approved.', 'CCCEM', 'Room for seminars, workshops, culminating activities, meetings, and training.', 30, 'Available', ['Projector', 'Projection Screen', 'Wall Fans', 'Air Conditioning', 'Internet Connection', 'Microphone', 'Speaker'], $standardProtocols, 'Emman E. Ramos', 'https://drive.google.com/drive/folders/1bXh2ULvFrNkWjR-zTMqL_mDLZ6UmoLUv?usp=sharing', 'Rate is not yet approved; internet is available as requested.'),
            $this->facility('College of Agriculture Conference Room', 'conference', 'Shared', 0, 'No rental fees.', 'College of Agriculture', 'Professional venue for academic and administrative meetings, seminars, workshops, consultations, and benchmarking.', 72, 'Available', ['Air Conditioning', 'Projector', 'Speaker', 'Microphone', 'Whiteboard', 'Internet Connection'], $noFoodProtocols, 'Dr. Jayson Juan, CAG', 'https://drive.google.com/drive/folders/1zkscs6r__p0Fud6I5wk7FmsRZICkNhie?usp=sharing'),
            $this->facility('DHTM Lounge', 'conference', 'Shared', 3500, '₱3,500 for 3 hours.', 'Department of Hospitality and Tourism Management', 'Air-conditioned learning and event venue for meetings, seminars, workshops, small events, and laboratory classes.', 60, 'Available', ['Air Conditioning', 'Television', 'Projector', 'Speaker', 'Microphone', 'Internet Connection'], $standardProtocols, 'Assoc. Prof. Celeste D. Dela Cruz, CHSI', 'https://drive.google.com/file/d/1H7MIZlTvIMOOopgVnsmKHrKCaEMZ_4YD/view?usp=sharing'),
            $this->facility('DHTM HTM6 Smart Classroom', 'classroom', 'Shared', 3500, '₱3,500 for 3 hours.', 'Department of Hospitality and Tourism Management', 'Technology-enhanced VR laboratory and smart classroom for instruction, meetings, seminars, workshops, and academic events.', 75, 'Available', ['Air Conditioning', 'Television', 'Projector', 'Internet Connection'], $noFoodProtocols, 'Assoc. Prof. Celeste D. Dela Cruz, CHSI', 'https://drive.google.com/drive/folders/1bErdtBk7uqyZ0CsT9CTB-uyWrhRIAHUK?usp=sharing', 'The description also notes 80 participants classroom-style or 60 in round-table arrangement.'),
            $this->facility('Armando N. Espino Jr. Training Hall (PreDiCt)', 'conference', 'Shared', 0, 'No rental fees.', 'Precision and Digital Agriculture Center', 'Modern venue for meetings, training, presentations, seminars, conferences, and group discussions.', 50, 'Available', ['Air Conditioning', 'Projector', 'Sound System', 'Television', 'Podium', 'Internet Connection', 'Other Audio-Visual Equipment'], $noFoodProtocols, 'Jonathan V. Fabula, PreDiCt', 'https://drive.google.com/drive/folders/1FT3VA4dWG3VL9twnmcRqd_jjnqn0uSXW?usp=sharing'),
            $this->facility('Armando N. Espino Jr. Conference Hall (PreDiCt)', 'conference', 'Shared', 0, 'No rental fees.', 'Precision and Digital Agriculture Center', 'Modern conference space for meetings, presentations, seminars, and group discussions.', 15, 'Available', ['Air Conditioning', 'Projector', 'Sound System', 'Television', 'Podium', 'Internet Connection', 'Other Audio-Visual Equipment'], $noFoodProtocols, 'Jonathan V. Fabula, PreDiCt', 'https://drive.google.com/drive/folders/1FT3VA4dWG3VL9twnmcRqd_jjnqn0uSXW?usp=sharing'),
            $this->facility('Umali Gymnasium', 'sports', 'Shared', null, 'To follow.', 'University Science High School', 'Recreational and sports venue for PE, athletic training, intramurals, student activities, and institutional programs.', null, 'Available', ['Stage'], $noFoodProtocols, 'Dr. Lexter R. Natividad, USHS Principal', 'https://drive.google.com/drive/folders/1N35XZgvk1RKk4Bo4XRbnGZE5eDs-Qpcw?usp=sharing', 'Capacity and rates are to follow.'),
            $this->facility('Alumni Social Hall', 'conference', 'Shared', 3000, '₱3,000 for 3 hours; ₱500 for each succeeding hour. 25% discount for CLSU alumni and 20% for senior citizens with Alumni ID.', 'CLSU Alumni Association Inc.', 'Multi-purpose facility for alumni gatherings, meetings, fellowships, worship services, and university-related social activities.', 100, 'Available', ['Television', 'Sound System', 'Whiteboard'], $standardProtocols, 'CLSU Alumni Association Inc. · (044) 803-9412 · 0955-991-0575', 'https://drive.google.com/drive/folders/1t3zhtEsC-PVyka4MgcdtivK2Te1IMLXZ?usp=sharing', 'Uses a separate booking reservation process; hostel bookings: https://alumni.clsu.edu.ph/hostel'),
        ];

        $excludedFacilityNames = collect($facilities)
            ->filter(fn (array $facility): bool => $facility['Capacity'] !== null && $facility['Capacity'] < 70)
            ->pluck('Facility_Name');

        // Archive only previously seeded directory entries that no longer meet
        // the minimum capacity. Unknown capacities remain until verified.
        Facilities::query()
            ->whereIn('Facility_Name', $excludedFacilityNames)
            ->each(fn (Facilities $facility) => $facility->delete());

        $facilities = collect($facilities)
            ->reject(fn (array $facility): bool => $facility['Capacity'] !== null && $facility['Capacity'] < 70)
            ->values()
            ->all();

        $amenityNames = collect($facilities)->pluck('amenities')->flatten()->unique()->sort()->values();
        $amenities = $amenityNames->mapWithKeys(function (string $name): array {
            $amenity = Amenities::withTrashed()->updateOrCreate(
                ['name' => $name],
                ['Description' => 'Facility-provided amenity listed in the official CLSU events and facilities directory.', 'Status' => 'Available', 'reservation_limit' => null]
            );
            if ($amenity->trashed()) {
                $amenity->restore();
            }

            return [$name => $amenity->AID];
        });

        foreach ($facilities as $data) {
            $facilityAmenities = $data['amenities'];
            unset($data['amenities']);

            $facility = Facilities::withTrashed()->updateOrCreate(
                ['Facility_Name' => $data['Facility_Name']],
                $data
            );
            if ($facility->trashed()) {
                $facility->restore();
            }
            $facility->amenities()->sync(collect($facilityAmenities)->map(fn (string $name) => $amenities[$name])->all());
        }
    }

    private function facility(
        string $name,
        string $type,
        string $accessType,
        ?float $price,
        string $rateDetails,
        string $office,
        string $description,
        ?int $capacity,
        string $status,
        array $amenities,
        string $protocols,
        ?string $contact = null,
        ?string $referenceUrl = null,
        ?string $notes = null,
    ): array {
        return [
            'Facility_Name' => $name,
            'facility_type' => $type,
            'Access_Type' => $accessType,
            'Price' => $price,
            'Rate_Details' => $rateDetails,
            'Office' => $office,
            'Description' => $description,
            'Protocols' => $protocols,
            'Contact_Details' => $contact,
            'Reference_URL' => $referenceUrl,
            'Data_Notes' => $notes,
            'Location' => 'Central Luzon State University, Science City of Muñoz, Nueva Ecija',
            'Capacity' => $capacity,
            'Status' => $status,
            'amenities' => $amenities,
        ];
    }
}
