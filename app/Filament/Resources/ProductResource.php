<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    #badge
    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getModel()::where('is_active', true)->count();
        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g. PRD-001')
                            ->helperText('Product code for identification'),

                        Forms\Components\Select::make('unit')
                            ->label('Unit')
                            ->required()
                            ->searchable()
                            ->options([
                                'pcs' => 'Pieces (pcs)',
                                'box' => 'Box',
                                'pack' => 'Pack',
                                'set' => 'Set',
                                'unit' => 'Unit',
                                'kg' => 'Kilogram (kg)',
                                'gram' => 'Gram',
                                'ton' => 'Ton',
                                'meter' => 'Meter (m)',
                                'cm' => 'Centimeter (cm)',
                                'mm' => 'Millimeter (mm)',
                                'inch' => 'Inch',
                                'feet' => 'Feet',
                                'liter' => 'Liter (L)',
                                'ml' => 'Milliliter (mL)',
                                'gallon' => 'Gallon',
                                'bottle' => 'Bottle',
                                'can' => 'Can',
                                'tube' => 'Tube',
                                'roll' => 'Roll',
                                'sheet' => 'Sheet',
                            ])
                            ->placeholder('Select unit')
                            ->helperText('Choose the measurement unit for this product')
                            ->native(false),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->minValue(0)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Toggle to activate/deactivate this product'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All products')
                    ->trueLabel('Active products')
                    ->falseLabel('Inactive products'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->where('is_active', true);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }
}