<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Enums\PayrollStatus;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Services\PayrollGenerator;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->status->value !== 'draft'),
        ];
    }

    protected function afterSave(): void
    {
        // The items were computed for the old period — rebuild them so the
        // draft matches what's on screen. Notes-only edits leave items alone
        // so manual overtime/bonus entries survive.
        if ($this->record->status !== PayrollStatus::Draft
            || ! $this->record->wasChanged(['pay_period_type', 'pay_period_start', 'pay_period_end'])) {
            return;
        }

        $result = (new PayrollGenerator)->regenerate($this->record);

        PayrollGenerator::notify('Payroll Regenerated', $result);
    }
}
