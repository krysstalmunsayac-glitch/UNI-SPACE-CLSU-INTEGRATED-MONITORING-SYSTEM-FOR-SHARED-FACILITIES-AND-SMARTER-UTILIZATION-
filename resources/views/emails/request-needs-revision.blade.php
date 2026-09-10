<x-mail::message>
# Action Needed on Your Request

Hello {{ $userName }},

Your facility request is still active. The reviewing office needs additional information before it can continue processing it.

<x-mail::panel>
**Request ID:** #{{ $requestId }}<br>
**Facility:** {{ $facilityName }}

**Information needed:**  
{{ $reviewNotes }}
</x-mail::panel>

<x-mail::button :url="$actionUrl">
Update My Request
</x-mail::button>

You do not need to submit a new request. Update the existing request with the information above, then save your changes for another review.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
