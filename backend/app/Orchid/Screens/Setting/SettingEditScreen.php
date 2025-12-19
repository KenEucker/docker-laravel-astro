<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Setting;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SettingEditScreen extends Screen
{
    /**
     * @var Setting
     */
    public $setting;

    /**
     * Query data.
     */
    public function query(Setting $setting): iterable
    {
        // Ensure $this->setting is populated for name/description/commandBar conditions
        $this->setting = $setting;

        return [
            'setting' => $setting,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->setting->exists ? 'Edit Setting' : 'Create Setting';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->setting->exists
            ? "Update setting: {$this->setting->key}"
            : 'Create a new application setting';
    }

    /**
     * Permission name.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.settings.edit',
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),

            Button::make(__('Remove'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Are you sure you want to delete this setting?'))
                ->canSee($this->setting->exists && auth()->user()->hasAccess('platform.settings.delete')),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('setting.key')
                    ->title('Key')
                    ->placeholder('e.g., APP_NAME, MAX_UPLOAD_SIZE')
                    ->help('Unique identifier for this setting')
                    ->required()
                    ->readonly($this->setting->exists),

                Select::make('setting.type')
                    ->title('Type')
                    ->options([
                        'string' => 'String (text)',
                        'integer' => 'Integer (whole number)',
                        'float' => 'Float (decimal number)',
                        'boolean' => 'Boolean (true/false)',
                        'json' => 'JSON (object/array)',
                        'array' => 'Array',
                        'object' => 'Object',
                    ])
                    ->required()
                    ->help('Data type for this setting'),

                TextArea::make('setting.value')
                    ->title('Value')
                    ->rows(5)
                    ->placeholder('Enter the value (format depends on type)')
                    ->help($this->getValueHelp()),

                TextArea::make('setting.description')
                    ->title('Description')
                    ->rows(3)
                    ->placeholder('Optional description of what this setting does')
                    ->help('Helps other admins understand the purpose of this setting'),

                CheckBox::make('setting.is_public')
                    ->title('Public Visibility')
                    ->placeholder('Is this setting visible to public API endpoints?')
                    ->help('Public settings can be accessed via /api/settings/public')
                    ->sendTrueOrFalse(),
            ]),
        ];
    }

    /**
     * Get help text for the value field.
     */
    private function getValueHelp(): string
    {
        return 'Examples:
• String: "Hello World"
• Integer: 100
• Float: 99.99
• Boolean: 1 (true) or 0 (false)
• JSON/Array/Object: {"key": "value"} or ["item1", "item2"]';
    }

    /**
     * Save setting.
     */
    public function save(Request $request, Setting $setting): void
    {
        $validated = $request->validate([
            'setting.key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('settings', 'key')->ignore($setting->id),
            ],
            'setting.value' => 'nullable|string',
            'setting.type' => [
                'required',
                Rule::in(['string', 'integer', 'float', 'boolean', 'json', 'array', 'object']),
            ],
            'setting.description' => 'nullable|string',
            'setting.is_public' => 'boolean',
        ]);

        $data = $validated['setting'];

        // Validate value based on type
        $this->validateValueByType($data['value'], $data['type']);

        // Use the Setting model's static method for consistency
        Setting::set(
            $data['key'],
            $data['value'],
            $data['type'],
            $data['description'] ?? null,
            (bool)($data['is_public'] ?? false)
        );

        Toast::success(__('Setting saved successfully'));
    }

    /**
     * Remove setting.
     */
    public function remove(Setting $setting): void
    {
        Setting::forget($setting->key);

        Toast::success(__('Setting deleted successfully'));
    }

    /**
     * Validate value based on type.
     */
    private function validateValueByType(?string $value, string $type): void
    {
        if ($value === null) {
            return;
        }

        switch ($type) {
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    throw new \InvalidArgumentException('Value must be a valid integer');
                }
                break;

            case 'float':
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException('Value must be a valid number');
                }
                break;

            case 'boolean':
                if (!in_array($value, ['0', '1', 'true', 'false'], true)) {
                    throw new \InvalidArgumentException('Value must be 0, 1, true, or false');
                }
                break;

            case 'json':
            case 'array':
            case 'object':
                $decoded = json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Value must be valid JSON: ' . json_last_error_msg());
                }
                break;
        }
    }
}
