<?php

namespace App\Filament\Resources\IpAddressGroups\Pages;

use App\Filament\Resources\IpAddressGroups\IpAddressGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIpAddressGroups extends ListRecords
{
    protected static string $resource = IpAddressGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
