<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Milon\Barcode\DNS1D;

class ProductResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'restore',
            'restore_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Menejemen Produk';

    // public static function getNavigationBadge(): ?string
    // {
    //     // return static::getModel()::count();
    //     return cache()->remember('badge_count_' . static::getModel(), now()->addMinutes(5), function () {
    //         return static::getModel()::count();
    //     });
    // }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ])->orderBy('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category_id')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('cost_price')
                    ->label('Harga Modal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Jual')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\FileUpload::make('image')
                    ->label('Gambar Produk')
                    ->directory('products')
                    ->helperText('jika tidak diisi akan diisi foto default')
                    ->image(),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->readOnly()
                    ->helperText('akan di generate otomatis')
                    ->maxLength(255),
                Forms\Components\TextInput::make('barcode')
                    ->label('Kode Barcode')
                    ->readOnly()
                    ->numeric()
                    ->helperText('akan di generate otomatis')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Produk Aktif')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi Produk')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->description(fn (Product $record): string => $record->category?->name ?? 'tanpa kategori')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Modal')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Jual')
                    ->prefix('Rp ')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('No.Barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Produk Aktif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->searchable(),

            ])
            ->actions([
                // Tables\Actions\Action::make('Reset Stok')
                //     ->action(fn (Product $record) => $record->update(['stock' => 0]))
                //     ->button()
                //     ->color('info')
                //     ->requiresConfirmation(),
                Tables\Actions\EditAction::make()
                    ->button(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
                Tables\Actions\BulkAction::make('printBarcodes')
                    ->label('Cetak Barcode')
                    ->button()
                    ->icon('heroicon-o-printer')
                    ->action(fn ($records) => self::generateBulkBarcode($records))
                    ->color('success'),

                // Tables\Actions\BulkAction::make('Reset Stok')
                //     ->action(fn ($records) => $records->each->update(['stock' => 0]))
                //     ->button()
                //     ->color('info')
                //     ->requiresConfirmation(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('printBarcodes')
                    ->label('Cetak Barcode')
                    ->icon('heroicon-o-printer')
                    ->action(fn () => self::generateBulkBarcode(Product::all()))
                    ->color('success'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
        ];
    }

    protected static function generateBulkBarcode($records)
    {
        $barcodes = [];
        $barcodeGenerator = new DNS1D;

        foreach ($records as $product) {
            $barcodes[] = [
                'name' => $product->name,
                'price' => $product->price,
                'barcode' => 'data:image/png;base64,'.$barcodeGenerator->getBarcodePNG($product->barcode, 'C128'),
                'number' => $product->barcode,
            ];
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.barcodes.barcode', compact('barcodes'))->setPaper('a4', 'portrait');

        // Kembalikan response download tanpa metode header()
        return response()->streamDownload(fn () => print ($pdf->output()), 'barcodes.pdf');
    }
}
