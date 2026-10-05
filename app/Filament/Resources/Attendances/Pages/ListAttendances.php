<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\ZkDevice;
use App\Services\ZkAttendanceService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAttendance')
                ->label('Sync Attendance')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => AttendanceResource::canCreate())
                ->action(function (ZkAttendanceService $attendanceService): void {
                    // The devices sit on store LANs the server can't reach, so
                    // this only flags them; each store's bridge script polls for
                    // the flag every minute and pulls from its device right away.
                    $requested = ZkDevice::where('is_active', true)->update(['sync_requested_at' => now()]);

                    // Punches already received from PINs that have since been
                    // assigned to an employee get folded into Attendance now.
                    $linked = $attendanceService->reconcileAllUnmappedPunches();

                    $body = $requested > 0
                        ? "Asked {$requested} device(s) to send their latest punches. They should appear within about a minute — refresh this page then."
                        : 'No active biometric devices are registered.';

                    if ($linked > 0) {
                        $body .= " Also linked {$linked} earlier punch(es) to employees.";
                    }

                    Notification::make()
                        ->title('Sync requested')
                        ->body($body)
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Record Attendance'),
        ];
    }
}
