<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class ScheduleForm extends Form
{
    public string $earliestDate = '';

    public string $noticeMessage = '';

    public ?int $Request_ID = null;

    public string $Date = '';

    public string $Start_Time = '08:00';

    public string $End_Time = '09:00';

    public string $Status = 'Booked';

    protected function rules(): array
    {
        return [
            'Request_ID' => ['required', 'exists:requests,RID'],
            'Date' => ['required', 'date', 'after_or_equal:'.$this->earliestDate],
            'Start_Time' => ['required', 'date_format:H:i'],
            'End_Time' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d|24:00$/', 'after:Start_Time'],
            'Status' => ['required', 'in:Booked,Blocked'],
        ];
    }

    protected function messages(): array
    {
        return ['Date.after_or_equal' => $this->noticeMessage];
    }
}
