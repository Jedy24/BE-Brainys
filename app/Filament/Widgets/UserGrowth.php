<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserGrowth extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Menghitung total pengguna
        $totalUsers = User::count();

        // Menghitung total pengguna aktif
        $totalActiveUsers = User::where('is_active', true)->count();

        // Menghitung total pengguna tidak aktif
        $totalNotActiveUsers = User::where('is_active', false)->count();

        // Menghitung pengguna yang dibuat pekan ini
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $usersThisWeek = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

        // Menghitung pengguna yang dibuat pekan lalu
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
        $usersLastWeek = User::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();

        // Menghitung persentase perubahan
        $percentageChange = $usersLastWeek > 0 ? (($usersThisWeek - $usersLastWeek) / $usersLastWeek) * 100 : 0;

        $description = round($percentageChange, 2) . '% change from last week';

        return [
            Stat::make('Total Users', $totalUsers)
                ->description($description)
                ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
            Stat::make('Total Active Users', $totalActiveUsers)
                ->description($description)
                ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
            Stat::make('Total Not Active Users', $totalNotActiveUsers)
                ->description($description)
                ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
        ];
    }
}