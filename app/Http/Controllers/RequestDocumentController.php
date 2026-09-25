<?php

namespace App\Http\Controllers;

use App\Models\FacilityRequest;
use App\Models\User;
use App\Notifications\PaymentProofUploaded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestDocumentController extends Controller
{
    public function downloadAttachment(Request $request, int $requestModel): StreamedResponse
    {
        $requestModel = FacilityRequest::withTrashed()->findOrFail($requestModel);
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

    public function viewAttachment(Request $request, int $requestModel): StreamedResponse
    {
        $requestModel = FacilityRequest::withTrashed()->findOrFail($requestModel);
        $user = $request->user();
        $isOwner = $requestModel->User_ID === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();
        $isAssignedAdmin = $user->isAdmin()
            && $requestModel->facility()
                ->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user->id))
                ->exists();

        abort_unless($isOwner || $isSuperAdmin || $isAssignedAdmin, 403);
        abort_unless($requestModel->attachment_path && Storage::disk('local')->exists($requestModel->attachment_path), 404);

        return Storage::disk('local')->response($requestModel->attachment_path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="request-'.$requestModel->RID.'-attachment.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function uploadPaymentProof(Request $request, FacilityRequest $requestModel)
    {
        abort_unless($requestModel->User_ID === $request->user()->id, 403);
        abort_unless($requestModel->Status === 'Awaiting Payment', 409, 'This request is not awaiting payment.');
        abort_unless(
            $requestModel->Payment_Deadline && now()->lte($requestModel->Payment_Deadline),
            409,
            'The payment deadline has passed. New or replacement receipts are no longer accepted.',
        );

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
            'Payment_Proof_Replacement_Reason' => null,
        ]);

        try {
            $requestModel->load('facility.assignedAdmins');
            $reviewers = User::query()->where('user_type', 'super_admin')->get()
                ->merge($requestModel->facility?->assignedAdmins ?? collect())
                ->unique('id')
                ->values();

            Notification::send($reviewers, new PaymentProofUploaded($requestModel));
        } catch (\Throwable $exception) {
            Log::warning('Payment proof uploaded, but reviewers could not be notified.', [
                'request_id' => $requestModel->RID,
                'exception' => $exception,
            ]);
        }

        return redirect()->route('dashboard', ['request' => $requestModel->RID])
            ->with('success', 'Your proof of payment was uploaded successfully and is ready for administrator review.');
    }

    public function downloadPaymentProof(Request $request, int $requestModel): StreamedResponse
    {
        $requestModel = FacilityRequest::withTrashed()->findOrFail($requestModel);
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

    public function viewPaymentProof(Request $request, int $requestModel): StreamedResponse
    {
        $requestModel = FacilityRequest::withTrashed()->findOrFail($requestModel);
        $user = $request->user();
        $isOwner = $requestModel->User_ID === $user->id;
        $isAuthorizedAdmin = $user->isSuperAdmin() || ($user->isAdmin()
            && $requestModel->facility()->whereHas('assignedAdmins', fn ($query) => $query->where('users.id', $user->id))->exists());

        abort_unless($isOwner || $isAuthorizedAdmin, 403);
        abort_unless($requestModel->Payment_Proof_Path && Storage::disk('local')->exists($requestModel->Payment_Proof_Path), 404);

        $path = $requestModel->Payment_Proof_Path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="payment-proof-request-'.$requestModel->RID.'.'.$extension.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
