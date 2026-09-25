<?php

use App\Livewire\Requests\RequestManagement;
use App\Models\User;
use Livewire\Livewire;

it('uses FIFO by default and toggles archived request columns with header chevrons', function () {
    $administrator = User::factory()->create([
        'user_type' => 'super_admin',
        'is_active' => true,
    ]);

    $this->actingAs($administrator);

    $component = Livewire::test(RequestManagement::class)
        ->set('archiveOnly', true)
        ->assertSet('archiveSortBy', 'deleted_at')
        ->assertSet('archiveSortDirection', 'asc')
        ->assertSee('Archived Requests')
        ->call('sortArchived', 'deleted_at')
        ->assertSet('archiveSortDirection', 'desc')
        ->call('sortArchived', 'facility')
        ->assertSet('archiveSortBy', 'facility')
        ->assertSet('archiveSortDirection', 'asc')
        ->call('sortArchived', 'facility')
        ->assertSet('archiveSortDirection', 'desc');

    foreach (['RID', 'requester', 'Proposed_Date', 'Proposed_Start_Time', 'event_type', 'Status'] as $column) {
        $component
            ->call('sortArchived', $column)
            ->assertSet('archiveSortBy', $column)
            ->assertSet('archiveSortDirection', 'asc');
    }
});
