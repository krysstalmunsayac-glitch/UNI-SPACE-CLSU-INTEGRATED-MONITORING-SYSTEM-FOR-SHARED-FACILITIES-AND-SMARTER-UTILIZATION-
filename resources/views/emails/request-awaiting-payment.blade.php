<x-mail::message>
# Action needed: Complete your payment

Hello {{ $userName }},

Your facility request is ready for payment. Please complete the steps below so an administrator can review it.

<x-mail::panel>
**Request:** #{{ $request->RID }}  
**Facility:** {{ $request->facility?->Facility_Name ?? 'N/A' }}  
**Amount due:** ₱{{ number_format((float) $request->Payment_Amount, 2) }}  
**Payment method:** Cash payment at the Admin Cashier  
**Pay on or before:** {{ $request->Payment_Deadline?->format('F j, Y g:i A') ?? 'Contact the administrator' }}
</x-mail::panel>

1. Pay the amount above in cash at the Admin Cashier.
2. Keep your official receipt.
3. Open your request in SIEL SPACE and upload a clear photo or PDF of the receipt.

<x-mail::button :url="$actionUrl">
Upload Payment Proof
</x-mail::button>

After you upload the receipt, an administrator will verify your payment. Your booking is not approved until that review is complete.

Thank you,  
SIEL SPACE  
Central Luzon State University
</x-mail::message>
