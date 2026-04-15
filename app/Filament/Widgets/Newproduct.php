<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductAlert extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Produk Terbaru'; // Ubah judul

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()->latest()->limit(10) // Ambil produk terbaru
            )
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price') // Ganti stok jadi harga
                    ->label('Harga Jual')
                    ->money('idr'),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Status Aktif'),
            ]);
    }
}