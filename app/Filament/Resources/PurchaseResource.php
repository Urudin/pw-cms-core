<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Mail\PurchaseAccessMail;
use App\Models\Purchase;
use App\Models\UserSetting;
use App\Models\Video;
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
    protected static ?string $navigationGroup = 'Megrendelések';
    protected static ?string $navigationLabel = 'Megrendelések';
    protected static ?string $modelLabel = 'Megrendelés';
    protected static ?string $pluralModelLabel = 'Megrendelések';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Személyes')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('personal_last_name')->label('Vezetéknév')->required()->maxLength(200),
                    Forms\Components\TextInput::make('personal_first_name')->label('Keresztnév')->required()->maxLength(200),
                    Forms\Components\TextInput::make('personal_phone')->label('Telefonszám')->required()->maxLength(50),
                    Forms\Components\TextInput::make('personal_email')->label('E-mail cím')->email()->required()->maxLength(255),
                    Forms\Components\Textarea::make('personal_note')->label('Vásárlói megjegyzés')->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Számlázási Adatok')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('billing_last_name')->label('Vezetéknév')->required()->maxLength(200),
                    Forms\Components\TextInput::make('billing_first_name')->label('Keresztnév')->required()->maxLength(200),
                    Forms\Components\TextInput::make('billing_company_name')->label('Cég neve')->maxLength(255),
                    Forms\Components\TextInput::make('billing_vat_number')->label('Adószám')->maxLength(100),
                    Forms\Components\TextInput::make('billing_address')->label('Telephely címe')->required()->maxLength(500)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Fizetési Adatok')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('payment_method')
                        ->label('Fizetési Mód')
                        ->required()
                        ->options(fn() => collect(config('payment_methods', []))
                            ->mapWithKeys(fn($m, $k) => [$k => $m['label'] ?? $k])
                            ->all()
                        ),

                    Forms\Components\Select::make('status')
                        ->label('Státusz')
                        ->required()
                        ->options([
                            'pending' => 'Függőben',
                            'paid' => 'Fizetve',
                            'failed' => 'Sikertelen',
                            'cancelled' => 'Megszakított',
                        ])
                        ->default('pending'),
//
//                    Forms\Components\Toggle::make('terms_accepted')->label('Feltételek elfogadva')->disabled(),
//                    Forms\Components\Toggle::make('privacy_accepted')->label('Adatkezelési tájékoztató elfogadva')->disabled(),
//                    Forms\Components\Toggle::make('newsletter_opt_in'),
//                    Forms\Components\Toggle::make('new_video_opt_in'),
                ]),

            Forms\Components\Section::make('Megvásárolt Videók')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('id')->label('Video ID')->disabled(),
                            Forms\Components\TextInput::make('quantity')->label('Mennyiség')->numeric()->disabled(),
                            Forms\Components\TextInput::make('title')->label('Cím')->disabled(),
                            Forms\Components\TextInput::make('price')->label('Ár')->numeric()->disabled(),
                        ])
                        ->afterStateHydrated(function (Forms\Components\Repeater $component, $state) {
                            // $state: [{"id": 2, "quantity": 1}]
                            $items = is_array($state) ? $state : [];

                            $ids = collect($items)->pluck('id')->filter()->unique()->values();

                            $videos = Video::query()
                                ->whereIn('id', $ids)
                                ->get(['id', 'title', 'price_huf']) // mezők igazítása
                                ->keyBy('id');

                            $enriched = collect($items)->map(function ($item) use ($videos) {
                                $video = $videos->get($item['id'] ?? null);

                                return [
                                    'id'       => $item['id'] ?? null,
                                    'quantity' => $item['quantity'] ?? 1,
                                    'title'    => $video?->title ?? '(törölt / nem található)',
                                    'price'    => $video?->price_huf, // vagy 0
                                ];
                            })->values()->all();

                            $component->state($enriched);
                        })
                        ->dehydrateStateUsing(function ($state) {
                            // Mentéskor csak az eredeti struktúrát tartsuk meg
                            return collect($state ?? [])
                                ->map(fn ($item) => [
                                    'id' => $item['id'] ?? null,
                                    'quantity' => (int) ($item['quantity'] ?? 1),
                                ])
                                ->filter(fn ($item) => ! empty($item['id']))
                                ->values()
                                ->all();
                        })
                        ->disabled()
                        ->columns(4),
                ]),

            Forms\Components\Section::make('Kereskedői Megjegyzés')
                ->schema([
                    Forms\Components\TextInput::make('client_ip')->hidden()->disabled(),
                    Forms\Components\Textarea::make('dealer_note')->columnSpanFull(),
                    Forms\Components\Textarea::make('user_agent')->hidden()->disabled()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('personal_email')
                    ->label('Megrendelő')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Fizetési mód')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'forward_payment' => 'Banki átutalás',
                        'card' => 'Kártyás fizetés',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Darab')
                    ->state(function ($record) {
                        $items = is_array($record->items)
                            ? $record->items
                            : json_decode($record->items ?? '[]', true);

                        return collect($items)->sum(fn ($item) => (int) ($item['quantity'] ?? 1));
                    })
                    ->alignCenter()
                    ->sortable(false),
                Tables\Columns\TextColumn::make('total_calc')
                    ->label('Összesen')
                    ->state(function ($record) {
                        $items = is_array($record->items) ? $record->items : json_decode($record->items ?? '[]', true);

                        $ids = collect($items)->pluck('id')->filter()->unique()->values();

                        if ($ids->isEmpty()) {
                            return 0;
                        }

                        $prices = Video::query()
                            ->whereIn('id', $ids)
                            ->pluck('price_huf', 'id'); // [id => price]

                        return collect($items)->sum(function ($item) use ($prices) {
                            $id = $item['id'] ?? null;
                            $qty = (int) ($item['quantity'] ?? 1);
                            $price = (int) ($prices[$id] ?? 0);

                            return $qty * $price;
                        });
                    })
                    ->money('HUF')
                    ->sortable(query: function ($query, string $direction) {
                        // JSON-os “összesen” számítás DB oldalon nem triviális és DB-függő (MySQL/PG)
                        // Ezért inkább tiltsuk a sort-ot, vagy csinálunk rá külön persisted oszlopot (B opció).
                        return $query;
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Dátum')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Státusz')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Függőben',
                        'paid' => 'Fizetve',
                        'cancelled' => 'Megszakított',
                        'failed' => 'Sikertelen',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Státusz')
                    ->options([
                        'pending' => 'Függőben',
                        'paid' => 'Fizetve',
                        'failed' => 'Sikertelen',
                        'cancelled' => 'Megszakított',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Fizetési mód')
                    ->options([
                        'forward_payment' => 'Banki átutalás',
                        'card' => 'Kártyás fizetés',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // 1) Fizetve
                Tables\Actions\Action::make('acknowledgePayment')
                    ->label('Fizetve')
                    ->icon('heroicon-o-banknotes')
                    ->color(fn (Purchase $record) => $record->status === 'paid' ? 'success' : 'primary')
                    ->disabled(fn (Purchase $record) => $record->status === 'paid')
                    ->tooltip(fn (Purchase $record) => $record->status === 'paid'
                        ? 'Már fizetve van'
                        : 'Státusz átállítása fizetetté'
                    )
                    ->requiresConfirmation(fn (Purchase $record) => $record->status !== 'paid')
                    ->action(function (Purchase $record) {
                        if ($record->status === 'paid') {
                            return;
                        }

                        $record->update(['status' => 'paid']);

                        Notification::make()
                            ->title('Státusz frissítve: fizetve')
                            ->success()
                            ->send();
                    }),

                // 2) Számla kiállítása (csak ha fizetve)
                Tables\Actions\Action::make('makeInvoice')
                    ->label(fn (Purchase $record) => (int) $record->billed === 1 ? 'Számla kiállítva' : 'Számla kiállítása')
                    ->icon('heroicon-o-paper-clip')
                    ->color(function (Purchase $record) {
                        if ((int) $record->billed === 1) return 'success';
                        if ($record->status !== 'paid') return 'gray';
                        return 'primary';
                    })
                    ->disabled(fn (Purchase $record) => (int) $record->billed === 1 || $record->status !== 'paid')
                    ->tooltip(function (Purchase $record) {
                        if ((int) $record->billed === 1) return 'A számla már ki lett állítva';
                        if ($record->status !== 'paid') return 'Számla csak fizetett státusz esetén állítható ki';
                        return 'Számla kiállítása';
                    })
                    ->requiresConfirmation(fn (Purchase $record) => (int) $record->billed !== 1 && $record->status === 'paid')
                    ->action(function (Purchase $record) {
                        if ($record->status !== 'paid' || (int) $record->billed === 1) {
                            return;
                        }

                        $record->issueInvoice($record);

                        // Ha az issueInvoice nem állítja, akkor itt állítsd:
                        // $record->update(['billed' => 1]);

                        Notification::make()
                            ->title('Számla kiállítva')
                            ->success()
                            ->send();
                    }),

                // 3) Hozzáférés küldése (csak számla után, többször küldhető, zöld ha volt már)
                Tables\Actions\Action::make('sendAccess')
                    ->label(fn (Purchase $record) => $record->access_sent ? 'Hozzáférés elküldve' : 'Hozzáférés küldése')
                    ->icon('heroicon-o-paper-airplane')
                    ->color(fn (Purchase $record) => $record->access_sent ? 'success' : 'primary')
                    ->disabled(fn (Purchase $record) => (int) $record->billed !== 1) // csak számla után
                    ->tooltip(fn (Purchase $record) => (int) $record->billed !== 1
                        ? 'Hozzáférés csak a számla kiállítása után küldhető'
                        : 'Hozzáférés e-mail újraküldése is lehetséges'
                    )
                    ->requiresConfirmation(fn (Purchase $record) => (int) $record->billed === 1)
                    ->action(function (Purchase $record) {
                        if ((int) $record->billed !== 1) {
                            return;
                        }

                        Mail::to([
                            $record->personal_email,
                            UserSetting::query()->firstWhere('name', 'admin-email-address')->value,
                        ])->send(new PurchaseAccessMail($record));

                        // boolean mező esetén:
                        //$record->update(['access_sent' => true]);

                        // ha inkább timestampet akarsz:
                        $record->update(['access_sent' => now()]);

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
            'view' => Pages\ViewPurchase::route('/{record}'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
