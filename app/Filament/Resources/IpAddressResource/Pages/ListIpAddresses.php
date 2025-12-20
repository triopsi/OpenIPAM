<?php

namespace App\Filament\Resources\IpAddressResource\Pages;

use App\Filament\Resources\IpAddressResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIpAddresses extends ListRecords
{
    protected static string $resource = IpAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
