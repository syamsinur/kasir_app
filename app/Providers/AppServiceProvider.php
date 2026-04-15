<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Report;
use App\Models\Transaction;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use App\Observers\ReportObserver;
use App\Observers\TransactionObserver;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inventory::observe(InventoryObserver::class);
        // InventoryItem::observe(InventoryItemObserver::class);
        // TransactionItem::observe(TransactionItemObserver::class);
        Transaction::observe(TransactionObserver::class);
        Category::observe(CategoryObserver::class);
        Product::observe(ProductObserver::class);
        Report::observe(ReportObserver::class);

        FilamentAsset::register([
            Js::make('printer-thermal', asset('js/printer-thermal.js'))
        ]);

        Model::preventLazyLoading();

        
    }
}
