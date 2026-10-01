<?php

namespace App\Filament\Resources\DateChangeRequests\Pages;

use App\Filament\Resources\DateChangeRequests\DateChangeRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDateChangeRequests extends ListRecords
{
    protected static string $resource = DateChangeRequestResource::class;
}
