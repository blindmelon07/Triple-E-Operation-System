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
        // If the acting admin is recording for someone other than their own
        // linked employee (or has no linked employee at all), stamp
        // recorded_by so it's clear this wasn't a self-service clock-in.
        $actingEmployeeId = Auth::user()?->employee?->id;

        if ($actingEmployeeId === null || (int) $data['employee_id'] !== $actingEmployeeId) {
            $data['recorded_by'] = Auth::id();
        }

        // Ensure total_hours is calculated
        if (! empty($data['time_in']) && ! empty($data['time_out']) && empty($data['total_hours'])) {
            $data['total_hours'] = Attendance::calculateTotalHours($data['time_in'], $data['time_out']);
        }

        return $data;
    }
}
