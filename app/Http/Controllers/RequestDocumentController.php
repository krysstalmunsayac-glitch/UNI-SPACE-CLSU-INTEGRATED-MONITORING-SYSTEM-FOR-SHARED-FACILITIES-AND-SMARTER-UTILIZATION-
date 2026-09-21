<?php

namespace App\Http\Controllers;

use App\Models\FacilityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestDocumentController extends Controller
{
    public function downloadAttachment(Request $request, FacilityRequest $requestModel): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $requestModel->User_ID === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();
        $isAssignedAdmin = $user->isAdmin()
            && $requestModel->facility()
                ->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user->id))
                ->exists();

        abort_unless($isOwner || $isSuperAdmin || $isAssignedAdmin, 403);
        abort_unless($requestModel->attachment_path, 404);

        $path = $requestModel->attachment_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->download(
            $path,
            "request-{$requestModel->RID}-attachment.{$extension}",
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }

    public function uploadPaymentProof(Request $request, FacilityRequest $requestModel)
    {
        abort_unless($requestModel->User_ID === $request->user()->id, 403);
        abort_unless($requestModel->Status === 'Awaiting Payment', 409, 'This request is not awaiting payment.');

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($requestModel->Payment_Proof_Path) {
            Storage::disk('local')->delete($requestModel->Payment_Proof_Path);
        }

        $path = $validated['payment_proof']->store('payment-proofs', 'local');
        $requestModel->update([
            'Payment_Proof_Path' => $path,
            'Payment_Proof_Uploaded_At' => now(),
        ]);

        return redirect()->route('dashboard', ['request' => $requestModel->RID])
            ->with('success', 'Your proof of payment was uploaded successfully and is ready for administrator review.');
    }

    public function downloadPaymentProof(Request $request, FacilityRequest $requestModel): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $requestModel->User_ID === $user->id;
        $isAuthorizedAdmin = $user->isSuperAdmin() || ($user->isAdmin()
            && $requestModel->facility()->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user->id))->exists());

        abort_unless($isOwner || $isAuthorizedAdmin, 403);
        abort_unless($requestModel->Payment_Proof_Path && Storage::disk('local')->exists($requestModel->Payment_Proof_Path), 404);

        return Storage::disk('local')->download(
            $requestModel->Payment_Proof_Path,
            'payment-proof-request-'.$requestModel->RID.'.'.pathinfo($requestModel->Payment_Proof_Path, PATHINFO_EXTENSION),
        );
    }
}
