<?php

namespace CustomSidebarManager\Pages;

use App\Facades\Plugin;
use App\Forms\Components\TinyEditor;
use App\Tables\Columns\IndexColumn;
use CustomSidebarManager\Models\CustomSidebar;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomSidebarManagerPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Custom Sidebar Manager';

    protected static string $view = 'CustomSidebarManager::page';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            CustomSidebarManagerPage::getUrl()  => 'Plugins',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CustomSidebar::query())
            ->columns([
                IndexColumn::make('no'),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                TableAction::make('edit')
                    ->label('Edit')
                    ->modalWidth('3xl')
                    ->fillForm(function (Model $record, Table $table): array {
                        $data = $record->attributesToArray();

                        return $data;
                    })
                    ->form($this->getFormSchemas())
                    ->action(function ($record, array $data) {
                        $record->name = $data['name'];
                        $record->content = $data['content'];
                        $record->show_name = $data['show_name'] ?? false;

                        $plugin = Plugin::getPlugin('CustomSidebarManager');
                        $customSidebars = $plugin->getSetting('custom_sidebars', []);

                        foreach ($customSidebars as $key => $sidebar) {
                            if ($sidebar['id'] == $record->id) {
                                $customSidebars[$key] = $record->toArray();
                            }
                        }

                        $plugin->updateSetting('custom_sidebars', $customSidebars);
                    }),
                TableAction::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $plugin = Plugin::getPlugin('CustomSidebarManager');
                        $customSidebars = $plugin->getSetting('custom_sidebars', []);

                        foreach ($customSidebars as $key => $sidebar) {
                            if ($sidebar['id'] == $record->id) {
                                unset($customSidebars[$key]);
                            }
                        }
                        $plugin->updateSetting('custom_sidebars', array_values($customSidebars));
                    }),
            ])
            ->bulkActions([
                // ...
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Custom Sidebar')
                ->action(function (array $data) {   
                    $data['id'] = rand(100000, 999999);

                    $plugin = Plugin::getPlugin('CustomSidebarManager');
                    $customSidebars = $plugin->getSetting('custom_sidebars', []);
                    $customSidebars[] = $data;

                    $plugin->updateSetting('custom_sidebars', $customSidebars);
                })
                ->modalWidth('3xl')
                ->form($this->getFormSchemas()),
        ];
    }

    public function getFormSchemas(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
            Toggle::make('show_name')
                ->label('Show the name of this sidebar above the content?')
                ->default(false),
            TinyEditor::make('content')
                ->label('Content')
                ->minHeight(300)
                ->plugins('advlist autoresize codesample directionality emoticons fullscreen hr image imagetools link lists media table toc wordcount code')
                ->toolbar('undo redo removeformat | formatselect fontsizeselect | bold italic | rtl ltr | alignjustify alignright aligncenter alignleft | numlist bullist | blockquote table hr | image link fullscreen | code')
                ->helperText('Content of the sidebar.'),
        ];
    }
}
