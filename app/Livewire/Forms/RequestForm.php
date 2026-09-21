<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class RequestForm extends Form
{
    public ?int $Event_ID = null;

    public ?int $User_ID = null;

    public string $Proposed_Date = '';

    public string $Proposed_End_Date = '';

    public string $Proposed_Start_Time = '';

    public string $Proposed_End_Time = '';

    public string $Status = 'Pending';

    public string $Purpose = '';

    public ?int $Capacity = null;

    protected function rules(): array
    {
        return [
            'Event_ID' => ['nullable', 'integer'],
            'User_ID' => ['nullable', 'integer'],
            'Proposed_Date' => ['required', 'date', 'after:today'],
            'Proposed_End_Date' => ['required', 'date', 'after_or_equal:Proposed_Date'],
            'Proposed_Start_Time' => ['required', 'date_format:H:i'],
            'Proposed_End_Time' => ['required', 'date_format:H:i', 'after:Proposed_Start_Time'],
            'Status' => ['required', 'in:Pending,Approved,Rejected,Cancelled'],
            'Purpose' => ['required', 'string', 'min:5', 'max:1000'],
            'Capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
