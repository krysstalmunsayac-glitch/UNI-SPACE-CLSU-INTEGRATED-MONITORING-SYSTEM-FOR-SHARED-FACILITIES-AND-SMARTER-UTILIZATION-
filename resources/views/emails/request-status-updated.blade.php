<x-mail::message>
# Your Request Status Changed

Hello {{ $userName }},

{{ $message }} Here are the latest reservation details:

<x-mail::panel>
**Request ID:** #{{ $requestId }}<br>
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}  
**Status:** {{ $status }}
@if ($status === 'Rejected' && $rejectionReason)

**Reason for rejection:** {{ $rejectionReason }}
@endif
</x-mail::panel>

<x-mail::button :url="$actionUrl">
View My Request
</x-mail::button>

You can open SIEL SPACE anytime to review the request and its latest status. If you do not recognize this activity, please contact the facility office.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
