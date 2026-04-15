<?php

namespace App\Filament\Widgets;use App\Models\CashFlow;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalStatsOverview extends BaseWidget
{
 
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = true;   protected static ?int $sort = 2;

    protected function getDescription(): ?string
    {
        return 'Total dari semua perhitungan';
    }

    protected function getHeading(): ?string
    {
        return 'Total Keseluruhan';
    }

    protected function getStats(): array
    {
        // $totalInFlow = CashFlow::where('type','income')->sum('amount');
        // $totalOutFlow = CashFlow::where('type','expense')->sum('amount');
          // Hitung SEMUA
        $totals = \App\Models\CashFlow::query()
            ->selectRaw("
            SUM(CASE WHEN type = 'income' AND source LIKE '%capital%' THEN amount ELSE 0 END) as total_capital,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
        ")
            ->first();

        // Simpan ke variabel
        $capital = $totals->total_capital ?? 0;
        $income = $totals->total_income ?? 0;
        $expense = $totals->total_expense ?? 0;
        $net = $income - $expense;

        // menampilkan variabel statis
        return [
            Stat::make('Total Uang Masuk', 'Rp '.number_format($income, 0, ',', '.'))
                ->description('Termasuk modal: Rp '.number_format($capital, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            // Stat::make('Total Uang Keluar', 'Rp '.number_format($expense, 0, ',', '.'))
            //     ->color('danger'),

            // Stat::make('Selisih (Kas)', 'Rp '.number_format($net, 0, ',', '.'))
            //     ->description('Total pendapatan dikurangi pengeluaran')
            //     ->color($net >= 0 ? 'primary' : 'danger'),
        ];
    }
}
