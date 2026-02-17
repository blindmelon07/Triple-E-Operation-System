<?php

namespace App\Filament\Resources\GovernmentContributions\Pages;

use App\Filament\Resources\GovernmentContributions\GovernmentContributionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGovernmentContributions extends ListRecords
{
    protected static string $resource = GovernmentContributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Contribution Bracket'),
        ];
    }
}
