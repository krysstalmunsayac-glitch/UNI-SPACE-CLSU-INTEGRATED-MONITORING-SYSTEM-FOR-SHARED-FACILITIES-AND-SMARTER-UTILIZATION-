<x-mail::message>
# Facility Request Cancelled

Hello {{ $adminName }},

A requester cancelled a facility request and provided a cancellation reason.

<x-mail::panel>
**Request #:** {{ $requestId }}  
**Requester:** {{ $requesterName }}{{ $requesterEmail ? ' <'.$requesterEmail.'>' : '' }}  
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}
</x-mail::panel>

**Cancellation reason**

{{ $reason }}

<x-mail::button :url="$actionUrl">
Open Request Management
</x-mail::button>

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
