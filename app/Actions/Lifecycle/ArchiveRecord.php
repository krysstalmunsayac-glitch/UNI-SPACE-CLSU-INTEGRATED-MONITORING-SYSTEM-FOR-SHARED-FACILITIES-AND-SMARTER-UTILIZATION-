<?php

namespace App\Actions\Lifecycle;

use Illuminate\Database\Eloquent\Model;

class ArchiveRecord
{
    public function handle(Model $record): void
    {
        $record->delete();
    }
}
