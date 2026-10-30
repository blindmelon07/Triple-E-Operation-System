<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Payroll;
use App\Services\PayrollGenerator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['payroll_number'] = Payroll::generatePayrollNumber();
        $data['status'] = 'draft';
        $data['generated_by'] = Auth::id();
        $data['total_gross'] = 0;
        $data['total_deductions'] = 0;
        $data['total_net'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $result = (new PayrollGenerator)->generate($this->record);

        if ($result['employees'] === 0) {
            Notification::make()
                ->title('No Employees Found')
                ->body('No employees found with the selected pay period type.'
                    .($result['missing_compensation']->isNotEmpty()
                        ? ' These employees have attendance but no compensation set up: '.$result['missing_compensation']->implode(', ').'.'
                        : ''))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        PayrollGenerator::notify('Payroll Generated!', $result);
    }
}
