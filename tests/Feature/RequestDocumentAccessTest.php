<?php

use App\Actions\Lifecycle\PermanentlyDeleteRecord;
use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('allows an owner to download documents from an archived request', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Archived Document Hall',
        'Status' => 'Available',
    ]);
    $attachmentPath = 'request-attachments/archived-request.pdf';
    $paymentProofPath = 'payment-proofs/archived-receipt.pdf';

    Storage::disk('local')->put($attachmentPath, 'request document');
    Storage::disk('local')->put($paymentProofPath, 'payment proof');

    $facilityRequest = FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->subDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Ended',
        'Purpose' => 'Archived document access test',
        'attachment_path' => $attachmentPath,
        'Payment_Proof_Path' => $paymentProofPath,
    ]);
    $facilityRequest->delete();

    $this->actingAs($user)
        ->get(route('requests.attachment.download', $facilityRequest->RID))
        ->assertOk()
        ->assertDownload("request-{$facilityRequest->RID}-attachment.pdf");

    $this->get(route('requests.attachment.view', $facilityRequest->RID))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('requests.payment-proof.download', $facilityRequest->RID))
        ->assertOk()
        ->assertDownload("payment-proof-request-{$facilityRequest->RID}.pdf");

    $this->get(route('requests.payment-proof.view', $facilityRequest->RID))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('allows an owner to give feedback for an archived completed reservation', function () {
    $user = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $facility = Facility::query()->create([
        'Facility_Name' => 'Archived Feedback Hall',
        'Status' => 'Available',
    ]);
    $facilityRequest = FacilityRequest::query()->create([
        'User_ID' => $user->id,
        'Facility_ID' => $facility->FID,
        'Proposed_Date' => today()->subDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Ended',
        'Purpose' => 'Archived feedback access test',
    ]);

    // Archiving the facility also archives its linked request.
    $facility->delete();

    $this->actingAs($user)
        ->get(route('requests.feedback.create', $facilityRequest->RID))
        ->assertOk()
        ->assertSee('Rate your facility');

    $this->post(route('requests.feedback.store', $facilityRequest->RID), [
        'Rating' => 5,
        'Reservation_Frequency' => 'First time',
        'Purpose_Importance' => 'Very Important',
        'Requirements_Met' => 'Yes, completely',
        'Reserve_Again' => 'Definitely Yes',
        'Comment' => 'The archived reservation remains available for feedback.',
    ])->assertRedirect(route('dashboard').'#requests');

    $this->assertDatabaseHas('feedbacks', [
        'User_ID' => $user->id,
        'Request_ID' => $facilityRequest->RID,
        'Rating' => 5,
    ]);
});

it('keeps archived request documents private from unrelated users', function () {
    Storage::fake('local');

    $owner = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $otherUser = User::factory()->create([
        'user_type' => 'user',
        'is_active' => true,
    ]);
    $attachmentPath = 'request-attachments/private-archived-request.pdf';
    $paymentProofPath = 'payment-proofs/private-archived-receipt.pdf';

    Storage::disk('local')->put($attachmentPath, 'private request document');
    Storage::disk('local')->put($paymentProofPath, 'private payment proof');

    $facilityRequest = FacilityRequest::query()->create([
        'User_ID' => $owner->id,
        'Proposed_Date' => today()->subDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Ended',
        'Purpose' => 'Private archived document test',
        'attachment_path' => $attachmentPath,
        'Payment_Proof_Path' => $paymentProofPath,
    ]);
    $facilityRequest->delete();

    $this->actingAs($otherUser)
        ->get(route('requests.attachment.download', $facilityRequest->RID))
        ->assertForbidden();

    $this->get(route('requests.attachment.view', $facilityRequest->RID))
        ->assertForbidden();

    $this->get(route('requests.payment-proof.download', $facilityRequest->RID))
        ->assertForbidden();

    $this->get(route('requests.payment-proof.view', $facilityRequest->RID))
        ->assertForbidden();
});

it('deletes private documents when an archived request is permanently deleted', function () {
    Storage::fake('local');

    $attachmentPath = 'request-attachments/permanently-deleted-request.pdf';
    $paymentProofPath = 'payment-proofs/permanently-deleted-receipt.pdf';
    Storage::disk('local')->put($attachmentPath, 'request document');
    Storage::disk('local')->put($paymentProofPath, 'payment proof');

    $facilityRequest = FacilityRequest::query()->create([
        'Proposed_Date' => today()->subDay()->toDateString(),
        'Proposed_Start_Time' => '09:00',
        'Proposed_End_Time' => '11:00',
        'Status' => 'Ended',
        'Purpose' => 'Permanent document cleanup test',
        'attachment_path' => $attachmentPath,
        'Payment_Proof_Path' => $paymentProofPath,
    ]);
    $requestId = $facilityRequest->RID;
    $facilityRequest->delete();

    app(PermanentlyDeleteRecord::class)->handle($facilityRequest);

    expect(FacilityRequest::withTrashed()->find($requestId))->toBeNull();
    Storage::disk('local')->assertMissing($attachmentPath);
    Storage::disk('local')->assertMissing($paymentProofPath);
});
