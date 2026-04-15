<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    private static function generateSlug(?string $title): string
    {
        if (!$title) {
            return '';
        }

        // Mapa polskich znaków na ich odpowiedniki bez akcentów
        $polishChars = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
            'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z',
        ];

        // Zamień polskie znaki
        $slug = strtr($title, $polishChars);
        
        // Zamień spacje na myślniki i konwertuj na lowercase
        $slug = Str::slug($slug);

        return $slug;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Tytuł')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if (!$state) {
                            return;
                        }
                        
                        // Mapa polskich znaków
                        $polishChars = [
                            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
                            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
                            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
                            'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z',
                        ];
                        
                        $slug = strtr($state, $polishChars);
                        $slug = Str::slug($slug);
                        $set('slug', $slug);
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('category')
                    ->label('Kategoria')
                    ->options([
                        'poradniki' => 'Poradniki',
                        'trendy' => 'Trendy',
                        'case-study' => 'Case Study',
                        'rynek-ooh' => 'Rynek OOH',
                        'prawo-i-regulacje' => 'Prawo i regulacje',
                        'lokalizacje' => 'Lokalizacje',
                    ])
                    ->default('rynek-ooh')
                    ->required(),
                Toggle::make('status')
                    ->label('Opublikowany')
                    ->onIcon('heroicon-m-check-badge')
                    ->offIcon('heroicon-m-x-mark')
                    ->afterStateHydrated(fn ($component, $state) => $component->state(
                        $state === null ? true : $state === 'published'
                    ))
                    ->dehydrateStateUsing(fn ($state) => $state ? 'published' : 'draft'),
                FileUpload::make('image')
                    ->label('Obrazek')
                    ->image()
                    ->disk('public')
                    ->directory('blog')
                    ->previewable(false)
                    ->nullable(),
                TextInput::make('image_alt')
                    ->label('Alt obrazka (SEO)')
                    ->placeholder('Krótki opis obrazka dla wyszukiwarek, np. "Billboard przy autostradzie A1 w Łodzi"')
                    ->maxLength(125)
                    ->nullable(),
                Textarea::make('image_prompt')
                    ->label('Prompt do generowania grafiki')
                    ->placeholder('Opisz grafikę do wygenerowania dla tego artykułu...')
                    ->rows(3)
                    ->nullable(),
                RichEditor::make('content')
                    ->label('Treść')
                    ->required(),
            ]);
    }
}
