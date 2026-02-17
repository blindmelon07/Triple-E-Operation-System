<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If an admin is recording for another user, set recorded_by
        if ((int) $data['user_id'] !== Auth::id()) {
            $data['recorded_by'] = Auth::id();
        }

        // Ensure total_hours is calculated
        if (! empty($data['time_in']) && ! empty($data['time_out']) && empty($data['total_hours'])) {
            $data['total_hours'] = Attendance::calculateTotalHours($data['time_in'], $data['time_out']);
        }

        return $data;
    }
}
