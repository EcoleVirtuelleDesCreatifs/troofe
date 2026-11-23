<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ArticleResource\Pages;
use App\Filament\Schemas\ListingForms;
use App\Models\Article;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string { return __('Contenus'); }

    public static function getModelLabel(): string { return __('article'); }

    public static function getPluralModelLabel(): string { return __('Actualités'); }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            TextInput::make('category')->label(__('Rubrique'))->required()->default('Actualité')->datalist(['Actualité', 'Portrait', 'Recette', 'Culture', 'Agenda', 'Enquête']),
            Select::make('status')->label(__('Statut'))->options(['draft' => __('Brouillon'), 'published' => __('Publié')])->default('draft')->required(),
            DateTimePicker::make('published_at')->label(__('Date de publication'))->default(now())->seconds(false),
            FileUpload::make('cover')->label(__('Image'))->image()->imageEditor()->directory('articles')->columnSpanFull(),
            Hidden::make('author_id')->default(fn () => auth()->id()),
            Tabs::make('langues')->columnSpanFull()->tabs([
                Tabs\Tab::make('Français')->schema([TextInput::make('title')->label(__('Titre'))->required(), Textarea::make('excerpt')->label(__('Chapô'))->rows(2), RichEditor::make('body')->label(__('Texte'))->required()->fileAttachmentsDirectory('articles')]),
                Tabs\Tab::make('English')->schema([TextInput::make('title_en')->label(__('Titre')), Textarea::make('excerpt_en')->label(__('Chapô'))->rows(2), RichEditor::make('body_en')->label(__('Texte'))->fileAttachmentsDirectory('articles')]),
            ]),
            ListingForms::seo()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('published_at', 'desc')->columns([
            Tables\Columns\ImageColumn::make('cover')->label('')->defaultImageUrl(fn ($record) => $record->coverUrl()),
            Tables\Columns\TextColumn::make('title')->label(__('Titre'))->searchable()->limit(60),
            Tables\Columns\TextColumn::make('category')->label(__('Rubrique'))->badge(),
            Tables\Columns\IconColumn::make('title_en')->label('EN')->boolean()->state(fn ($record) => filled($record->title_en)),
            Tables\Columns\TextColumn::make('published_at')->label(__('Publication'))->dateTime('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('status')->label(__('Statut'))->badge()->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            Tables\Columns\TextColumn::make('views')->label(__('Vues'))->numeric(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageArticles::route('/')];
    }
}
