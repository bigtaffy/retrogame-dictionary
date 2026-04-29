<?php

namespace App\Filament\Resources\Games\RelationManagers;

use App\Models\Cheat;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Embed 在 Edit Game 頁面，讓編輯遊戲時可直接管秘技。
 */
class CheatsRelationManager extends RelationManager
{
    protected static string $relationship = 'cheats';

    protected static ?string $title = '秘技 Cheats';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('基本')->columnSpanFull()->schema([
                Select::make('type')
                    ->label('種類')
                    ->options(Cheat::TYPES)
                    ->required()
                    ->native(false),
                Select::make('difficulty')
                    ->label('難度')
                    ->options(Cheat::DIFFICULTIES)
                    ->default('easy')
                    ->required(),
                Select::make('region')
                    ->label('地區')
                    ->options(['jp' => 'JP', 'us' => 'US', 'eu' => 'EU'])
                    ->placeholder('全 region 通用')
                    ->native(false),
                TextInput::make('rom_version')
                    ->label('ROM 版本')
                    ->maxLength(32),
            ]),

            Section::make('效果')->columnSpanFull()->schema([
                TextInput::make('effect_zh')
                    ->label('效果（中）')
                    ->placeholder('無敵 / 99 條命 / 選關')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('effect_en')
                    ->label('Effect (EN)')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('effect_jp')
                    ->label('効果（日）')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description_zh')
                    ->label('詳細（中）')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->label('Description (EN)')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),

            Section::make('內容')->columnSpanFull()->schema([
                Textarea::make('code')
                    ->label('Code')
                    ->placeholder('↑↑↓↓←→←→BA / hex / 密碼')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('trigger_at')
                    ->label('觸發位置')
                    ->placeholder('標題畫面 / Stage 1 暫停')
                    ->maxLength(128)
                    ->columnSpanFull(),
            ]),

            Section::make('來源 + 審核')->columnSpanFull()->schema([
                TextInput::make('source')
                    ->label('來源')
                    ->maxLength(64),
                TextInput::make('source_url')
                    ->label('URL')
                    ->url()
                    ->maxLength(512),
                Toggle::make('verified')
                    ->label('已驗證')
                    ->default(false),
                TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
            ]),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('effect_zh')
            ->columns([
                TextColumn::make('type')
                    ->label('種類')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Cheat::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'button_sequence' => 'info',
                        'password' => 'warning',
                        'game_genie', 'pro_action_replay', 'memory_patch' => 'danger',
                        'glitch' => 'gray',
                        'easter_egg' => 'success',
                        'unlock' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('effect_zh')
                    ->label('效果（中）')
                    ->limit(36)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('code')
                    ->label('Code')
                    ->limit(24)
                    ->fontFamily('mono')
                    ->copyable()
                    ->tooltip(fn (Cheat $r): string => (string) $r->code),
                TextColumn::make('difficulty')
                    ->label('難度')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Cheat::DIFFICULTIES[$state] ?? $state),
                TextColumn::make('region')
                    ->label('地區')
                    ->badge()
                    ->placeholder('全'),
                IconColumn::make('verified')
                    ->label('驗證')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                SelectFilter::make('type')->options(Cheat::TYPES),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
