<x-mail::message>
# Your Request Needs an Update

Hello {{ $userName }},

Your facility request has **not been rejected**. The reviewing office needs additional information before it can continue processing the same request.

<x-mail::panel>
**Request #:** {{ $requestId }}  
**Facility:** {{ $facilityName }}

**Information needed:**  
{{ $reviewNotes }}
</x-mail::panel>

<x-mail::button :url="$actionUrl">
Update the same request
</x-mail::button>

You do not need to submit a new request. Open your existing request, provide the requested information, and save your changes.

Thank you,  
CLSU Uni Space  
Central Luzon State University
</x-mail::message>
