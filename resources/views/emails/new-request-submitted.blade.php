<x-mail::message>
# New Request Ready for Review

Hello {{ $adminName }},

A new facility request has been submitted. Please review the reservation details below and take the appropriate action.

<x-mail::panel>
**Request ID:** #{{ $requestId }}<br>
**Requester:** {{ $requesterName }}{{ $requesterEmail ? ' <'.$requesterEmail.'>' : '' }}  
@if ($createdBy)
**Created by:** {{ $createdBy }}<br>
@endif
**Facility:** {{ $facilityName }}  
**Date:** {{ $proposedDate }}  
**Time:** {{ $startTime }} - {{ $endTime }}  
**Expected attendees:** {{ $expectedCapacity }}  
**Status:** {{ $status }}
</x-mail::panel>

**Purpose**

{{ $purpose }}

<x-mail::button :url="$actionUrl">
Review Facility Request
</x-mail::button>

This notification was sent to help your office respond promptly and keep the requester informed.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
