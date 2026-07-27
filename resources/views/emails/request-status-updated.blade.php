<x-mail::message>
# Facility Request Update

Hello {{ $userName }},

{{ $message }}

<x-mail::panel>
**Request #:** {{ $requestId }}  
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}  
**Status:** {{ $status }}
@if ($status === 'Rejected' && $rejectionReason)

**Reason for rejection:** {{ $rejectionReason }}
@endif
</x-mail::panel>

<x-mail::button :url="$actionUrl">
View request
</x-mail::button>

If you did not submit this request, please contact the facility office.

Thank you,  
CLSU Uni Space  
Central Luzon State University
</x-mail::message>
