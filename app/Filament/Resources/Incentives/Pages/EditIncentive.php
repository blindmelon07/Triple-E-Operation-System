<?php

namespace App\Filament\Resources\Incentives\Pages;

use App\Filament\Resources\Incentives\IncentiveResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncentive extends EditRecord
{
    protected static string $resource = IncentiveResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Already in a live payroll or released — editing would make the
        // payslip or the release record wrong.
        abort_if($this->record->isLocked(), 403, 'This incentive is already in a payroll or released.');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
