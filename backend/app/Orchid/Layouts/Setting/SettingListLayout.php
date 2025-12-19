<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Setting;

use App\Models\Setting;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SettingListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    public $target = 'settings';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('key', 'Key')
                ->sort()
                ->filter(Input::make())
                ->render(fn (Setting $setting) => Link::make($setting->key)
                    ->route('platform.settings.edit', $setting)),

            TD::make('value', 'Value')
                ->render(fn (Setting $setting) => $this->formatValue($setting->value, $setting->type)),

            TD::make('type', 'Type')
                ->sort()
                ->filter(Input::make())
                ->render(fn (Setting $setting) => "<span class='badge bg-info'>{$setting->type}</span>"),

            TD::make('is_public', 'Visibility')
                ->sort()
                ->render(fn (Setting $setting) => $setting->is_public
                    ? "<span class='badge bg-success'>Public</span>"
                    : "<span class='badge bg-secondary'>Private</span>"),

            TD::make('description', 'Description')
                ->render(fn (Setting $setting) => $setting->description
                    ? \Illuminate\Support\Str::limit($setting->description, 50)
                    : '-'),

            TD::make('actions', 'Actions')
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (Setting $setting) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->icon('bs.pencil')
                            ->route('platform.settings.edit', $setting)
                            ->canSee(auth()->user()->hasAccess('platform.settings.edit')),

                        Button::make(__('Delete'))
                            ->icon('bs.trash')
                            ->method('remove')
                            ->confirm(__('Are you sure you want to delete this setting?'))
                            ->parameters(['id' => $setting->id])
                            ->canSee(auth()->user()->hasAccess('platform.settings.delete')),
                    ])),
        ];
    }

    /**
     * Format value for display.
     */
    private function formatValue(?string $value, string $type): string
    {
        if ($value === null) {
            return '<em class="text-muted">null</em>';
        }

        $displayValue = match ($type) {
            'boolean', 'bool' => $value ? '✓ true' : '✗ false',
            'array', 'json', 'object' => \Illuminate\Support\Str::limit($value, 50),
            default => \Illuminate\Support\Str::limit($value, 50),
        };

        return htmlspecialchars($displayValue);
    }
}
