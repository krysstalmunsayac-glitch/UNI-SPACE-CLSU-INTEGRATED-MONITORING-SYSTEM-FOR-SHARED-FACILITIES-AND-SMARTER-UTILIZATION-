<x-mail::message>
# Request Cancelled by Requester

Hello {{ $adminName }},

A requester has cancelled a facility request. The reservation details and submitted reason are shown below.

<x-mail::panel>
**Request ID:** #{{ $requestId }}<br>
**Requester:** {{ $requesterName }}{{ $requesterEmail ? ' <'.$requesterEmail.'>' : '' }}  
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}
</x-mail::panel>

**Cancellation reason**

{{ $reason }}

<x-mail::button :url="$actionUrl">
Review Cancellation
</x-mail::button>

No approval action is required. Open Request Management if you need to review or archive the record.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
