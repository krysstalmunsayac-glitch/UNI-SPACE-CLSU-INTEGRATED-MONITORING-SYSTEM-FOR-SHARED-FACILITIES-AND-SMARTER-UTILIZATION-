<x-mail::message>
# New Facility Request

Hello {{ $adminName }},

A new facility request has been submitted and is ready for review.

<x-mail::panel>
**Request #:** {{ $requestId }}  
**Requester:** {{ $requesterName }}{{ $requesterEmail ? ' <'.$requesterEmail.'>' : '' }}  
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}  
**Expected attendees:** {{ $expectedCapacity }}  
**Status:** {{ $status }}
</x-mail::panel>

**Purpose**

{{ $purpose }}

<x-mail::button :url="$actionUrl">
Open Request Management
</x-mail::button>

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
