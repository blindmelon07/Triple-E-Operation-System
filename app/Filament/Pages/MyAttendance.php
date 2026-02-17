<?php

namespace App\Filament\Pages;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MyAttendance extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $navigationLabel = 'My Attendance';

    protected static ?string $title = 'My Attendance';

    protected static string|UnitEnum|null $navigationGroup = 'Attendance Management';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.my-attendance';

    public function getTodayAttendance(): ?Attendance
    {
        return Attendance::where('user_id', Auth::id())
            ->whereDate('date', today())
            ->first();
    }

    public function clockIn(): void
    {
        $existing = $this->getTodayAttendance();

        if ($existing && $existing->time_in) {
            Notification::make()
                ->title('Already Clocked In')
                ->body('You have already clocked in today at '.\Carbon\Carbon::parse($existing->time_in)->format('h:i A'))
                ->warning()
                ->send();

            return;
        }

        $now = now();
        $lateThreshold = today()->setHour(9)->setMinute(0);
        $status = $now->greaterThan($lateThreshold)
            ? AttendanceStatus::Late
            : AttendanceStatus::Present;

        if ($existing) {
            $existing->update([
                'time_in' => $now->format('H:i:s'),
                'status' => $status,
            ]);
        } else {
            Attendance::create([
                'user_id' => Auth::id(),
                'date' => today(),
                'time_in' => $now->format('H:i:s'),
                'status' => $status,
            ]);
        }

        Notification::make()
            ->title('Clocked In!')
            ->body('Time in recorded at '.$now->format('h:i A'))
            ->success()
            ->send();
    }

    public function clockOut(): void
    {
        $existing = $this->getTodayAttendance();

        if (! $existing || ! $existing->time_in) {
            Notification::make()
                ->title('Not Clocked In')
                ->body('You need to clock in first.')
                ->danger()
                ->send();

            return;
        }

        if ($existing->time_out) {
            Notification::make()
                ->title('Already Clocked Out')
                ->body('You have already clocked out today.')
                ->warning()
                ->send();

            return;
        }

        $now = now();
        $totalHours = Attendance::calculateTotalHours(
            $existing->time_in,
            $now->format('H:i:s')
        );

        $status = $existing->status;
        if ($totalHours && $totalHours < 4.5) {
            $status = AttendanceStatus::HalfDay;
        }

        $existing->update([
            'time_out' => $now->format('H:i:s'),
            'total_hours' => $totalHours,
            'status' => $status,
        ]);

        Notification::make()
            ->title('Clocked Out!')
            ->body('Time out recorded at '.$now->format('h:i A').'. Total: '.$totalHours.' hrs')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Attendance::query()
                    ->where('user_id', Auth::id())
            )
            ->columns([
                TextColumn::make('date')
                    ->date('M d, Y (D)')
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('Time In')
                    ->time('h:i A'),

                TextColumn::make('time_out')
                    ->label('Time Out')
                    ->time('h:i A'),

                TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->suffix(' hrs'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AttendanceStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (AttendanceStatus $state): string => $state->getLabel()),

                TextColumn::make('remarks')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10]);
    }
}
