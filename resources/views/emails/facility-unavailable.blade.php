<x-mail::message>
# Important Facility Update

Hello {{ $userName }},

We’re sorry, but **{{ $facilityName }}** is no longer available for the requested schedule. Your request and any linked reservation have been cancelled automatically.

<x-mail::panel>
**Request ID:** #{{ $requestId }}<br>
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}  
**Status:** Cancelled
</x-mail::panel>

<x-mail::button :url="$actionUrl">
Find Another Facility
</x-mail::button>

Please choose another available facility in SIEL SPACE. The facility office can assist you if you need help finding an alternative.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
