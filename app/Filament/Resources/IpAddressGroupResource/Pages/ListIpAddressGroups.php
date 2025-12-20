<?php

namespace App\Filament\Resources\IpAddressGroupResource\Pages;

use App\Filament\Resources\IpAddressGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIpAddressGroups extends ListRecords
{
    protected static string $resource = IpAddressGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
