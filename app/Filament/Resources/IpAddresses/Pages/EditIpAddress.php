<?php

namespace App\Filament\Resources\IpAddresses\Pages;

use App\Filament\Resources\IpAddresses\IpAddressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIpAddress extends EditRecord
{
    protected static string $resource = IpAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
