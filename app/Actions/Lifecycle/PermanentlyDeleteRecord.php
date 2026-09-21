<?php

namespace App\Actions\Lifecycle;

use Illuminate\Database\Eloquent\Model;

class PermanentlyDeleteRecord
{
    public function handle(Model $record): void
    {
        abort_unless(method_exists($record, 'trashed') && $record->trashed(), 409, 'Only archived records can be permanently deleted.');
        $record->forceDelete();
    }
}
