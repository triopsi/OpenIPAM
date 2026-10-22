<?php

namespace App\Filament\Resources\IpAddresses\Pages;

use App\Filament\Pages\Subnets;
use App\Filament\Resources\IpAddresses\IpAddressResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIpAddresses extends ListRecords
{
    protected static string $resource = IpAddressResource::class;

    /** Provide address creation and direct access to the subnet occupancy explorer. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('subnetGrid')
                ->label(__('subnets.title'))
                ->icon('heroicon-o-squares-2x2')
                ->url(fn (): string => Subnets::getUrl()),
            CreateAction::make(),
        ];
    }
}
