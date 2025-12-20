<?php

namespace App\Filament\Resources\IpAddressResource\Pages;

use App\Filament\Resources\IpAddressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIpAddress extends EditRecord
{
    protected static string $resource = IpAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
