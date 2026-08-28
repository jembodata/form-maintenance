<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ShieldManagement extends Cluster
{
    protected static ?string $title = 'Shield';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function shouldRegisterNavigation(): bool
    {
        // Tidak tampil di menu utama.
        return false;
    }
}
