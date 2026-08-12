<x-mail::message>
# Facility Unavailable

Hello {{ $userName }},

We’re sorry, but **{{ $facilityName }}** has been marked unavailable by the facility administrator. Your request and any linked schedule have therefore been cancelled automatically.

<x-mail::panel>
**Request #:** {{ $requestId }}  
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}  
**Status:** Cancelled
</x-mail::panel>

<x-mail::button :url="$actionUrl">
View your requests
</x-mail::button>

Please choose another available facility or contact the facility office if you need assistance.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
