<?php

namespace App\Filament\Resources\Incentives\Pages;

use App\Filament\Resources\Incentives\IncentiveResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateIncentive extends CreateRecord
{
    protected static string $resource = IncentiveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
