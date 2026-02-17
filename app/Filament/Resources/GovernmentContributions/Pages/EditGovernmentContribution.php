<?php

namespace App\Filament\Resources\GovernmentContributions\Pages;

use App\Filament\Resources\GovernmentContributions\GovernmentContributionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGovernmentContribution extends EditRecord
{
    protected static string $resource = GovernmentContributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
