<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['time_in']) && ! empty($data['time_out'])) {
            $data['total_hours'] = Attendance::calculateTotalHours($data['time_in'], $data['time_out']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
