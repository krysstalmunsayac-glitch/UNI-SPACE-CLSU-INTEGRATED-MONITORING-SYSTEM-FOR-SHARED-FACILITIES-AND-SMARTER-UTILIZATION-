<x-mail::message>
# Payment Required

Hello {{ $userName }},

Your facility request requires payment before it can be approved.

<x-mail::panel>
**Request:** #{{ $request->RID }}  
**Facility:** {{ $request->facility?->Facility_Name ?? 'N/A' }}  
**Amount due:** ₱{{ number_format((float) $request->Payment_Amount, 2) }}  
**Payment method:** Cash payment at the Admin Cashier  
**Deadline:** {{ $request->Payment_Deadline?->format('F j, Y g:i A') }}
</x-mail::panel>

After paying, open your request in SIEL SPACE and upload a clear photo or PDF of the official receipt under **Payment proof**.

<x-mail::button :url="$actionUrl">
Upload Payment Proof
</x-mail::button>

Please keep your original receipt for verification. Your booking is not fully approved until an administrator reviews the payment.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
