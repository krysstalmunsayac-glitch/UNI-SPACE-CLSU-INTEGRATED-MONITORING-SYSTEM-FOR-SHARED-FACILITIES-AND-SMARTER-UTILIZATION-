<x-mail::message>
# Your Reservation Schedule Changed

Hello {{ $userName }},

An administrator updated the date or time of your facility reservation.

<x-mail::panel>
**Request ID:** #{{ $requestId }}<br>
**Facility:** {{ $facilityName }}<br><br>
**Previous schedule:** {{ $oldSchedule['Date'] }} from {{ $oldSchedule['Start_Time'] }} to {{ $oldSchedule['End_Time'] }}<br>
**New schedule:** {{ $newSchedule['Date'] }} from {{ $newSchedule['Start_Time'] }} to {{ $newSchedule['End_Time'] }}
</x-mail::panel>

<x-mail::button :url="$actionUrl">
Review Reservation
</x-mail::button>

If you have questions about this change, please contact the facility office.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
