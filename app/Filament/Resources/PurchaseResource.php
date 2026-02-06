<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Mail\PurchaseAccessMail;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Vásárlások';
    protected static ?string $modelLabel = 'Vásárlás';
    protected static ?string $pluralModelLabel = 'Vásárlások';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('personal_last_name')->required()->maxLength(200),
                    Forms\Components\TextInput::make('personal_first_name')->required()->maxLength(200),
                    Forms\Components\TextInput::make('personal_phone')->required()->maxLength(50),
                    Forms\Components\TextInput::make('personal_email')->email()->required()->maxLength(255),
                    Forms\Components\Textarea::make('personal_note')->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Billing')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('billing_last_name')->required()->maxLength(200),
                    Forms\Components\TextInput::make('billing_first_name')->required()->maxLength(200),
                    Forms\Components\TextInput::make('billing_company_name')->maxLength(255),
                    Forms\Components\TextInput::make('billing_vat_number')->maxLength(100),
                    Forms\Components\TextInput::make('billing_address')->required()->maxLength(500)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Payment & Declarations')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('payment_method')
                        ->required()
                        ->options(fn () => collect(config('payment_methods', []))
                            ->mapWithKeys(fn ($m, $k) => [$k => $m['label'] ?? $k])
                            ->all()
                        ),

                    Forms\Components\Select::make('status')
                        ->required()
                        ->options([
                            'pending' => 'Várakozik',
                            'paid' => 'Fizetett',
                            'failed' => 'Sikertelen',
                            'cancelled' => 'Megszakított',
                        ])
                        ->default('pending'),

                    Forms\Components\Toggle::make('terms_accepted')->disabled(),
                    Forms\Components\Toggle::make('privacy_accepted')->disabled(),
                    Forms\Components\Toggle::make('newsletter_opt_in'),
                    Forms\Components\Toggle::make('new_video_opt_in'),
                ]),

            Forms\Components\Section::make('Items')
                ->schema([
                    Forms\Components\KeyValue::make('items')
                        ->helperText('JSON items payload (id, quantity, etc.)')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Totals')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('currency')->disabled(),
                    Forms\Components\TextInput::make('subtotal')->numeric(),
                    Forms\Components\TextInput::make('vat_rate')->numeric(),
                    Forms\Components\TextInput::make('vat_amount')->numeric(),
                    Forms\Components\TextInput::make('total')->numeric(),
                ]),

            Forms\Components\Section::make('Meta')
                ->schema([
                    Forms\Components\TextInput::make('client_ip')->disabled(),
                    Forms\Components\Textarea::make('user_agent')->disabled()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('personal_email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('payment_method')->label('Payment')->sortable(),
                Tables\Columns\TextColumn::make('total')->money('HUF')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('acknowledgePayment')
                    ->label('Acknowledge payment')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (Purchase $record) => $record->status === 'pending')
                    ->action(function (Purchase $record) {
                        Mail::to($record->personal_email)->send(new PurchaseAccessMail($record));
                        $record->update(['status' => 'paid']);

                        Notification::make()
                            ->title('E-mail elküldve')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'view'  => Pages\ViewPurchase::route('/{record}'),
            'edit'  => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
