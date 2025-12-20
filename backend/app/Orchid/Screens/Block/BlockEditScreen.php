<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Block;

use App\Models\Block;
use App\Services\BlockTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BlockEditScreen extends Screen
{
    /**
     * @var Block
     */
    public $block;

    /**
     * Query data.
     */
    public function query(Block $block): iterable
    {
        $this->block = $block;

        return [
            'block' => $block,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->block->exists ? 'Edit Block' : 'Create Block';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->block->exists
            ? "Editing block: {$this->block->key}"
            : 'Create a new content block';
    }

    /**
     * Permission name.
     */
    public function permission(): ?iterable
    {
        $exists = $this->block?->exists ?? false;

        return $exists
            ? ['platform.blocks.edit']
            : ['platform.blocks.create'];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save Draft'))
                ->icon('bs.check-circle')
                ->method('saveDraft')
                ->canSee(
                    (! $this->block->locked || auth()->user()->hasAccess('platform.blocks.manage_locked'))
                ),

            Button::make(__('Publish'))
                ->icon('bs.cloud-upload')
                ->method('publish')
                ->canSee(
                    auth()->user()->hasAccess('platform.blocks.publish') &&
                    (! $this->block->locked || auth()->user()->hasAccess('platform.blocks.manage_locked'))
                ),

            Button::make(__('Unpublish'))
                ->icon('bs.cloud-slash')
                ->method('unpublish')
                ->canSee(
                    $this->block->exists &&
                    $this->block->status === 'published' &&
                    auth()->user()->hasAccess('platform.blocks.publish') &&
                    (! $this->block->locked || auth()->user()->hasAccess('platform.blocks.manage_locked'))
                ),

            Button::make(__('Delete'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Are you sure you want to delete this block?'))
                ->canSee(
                    $this->block->exists &&
                    auth()->user()->hasAccess('platform.blocks.delete') &&
                    (! $this->block->locked || auth()->user()->hasAccess('platform.blocks.manage_locked'))
                ),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        $user = auth()->user();

        // Get available block types based on user permissions
        $blockTypes = BlockTypeRegistry::getTypesForSelect();

        // Filter out HTML type if user doesn't have permission
        if (! $user->hasAccess('platform.blocks.manage_html')) {
            unset($blockTypes['html']);
        }

        return [
            Layout::rows([
                Group::make([
                    Input::make('block.key')
                        ->title('Block Key')
                        ->placeholder('e.g., homepage.hero')
                        ->help('Unique identifier used by frontend to request this block')
                        ->required()
                        ->disabled($this->block->exists), // Disable editing key for existing blocks

                    Select::make('block.type')
                        ->title('Block Type')
                        ->options($blockTypes)
                        ->help('Type of content block')
                        ->required()
                        ->disabled($this->block->exists), // Disable changing type for existing blocks
                ]),

                Group::make([
                    Select::make('block.visibility')
                        ->title('Visibility')
                        ->options([
                            '' => 'Public',
                            'auth' => 'Authenticated Users Only',
                            'role:admin' => 'Admins Only',
                        ])
                        ->help('Control who can access this block')
                        ->canSee($user->hasAccess('platform.blocks.manage_visibility')),

                    CheckBox::make('block.locked')
                        ->title('Lock Block')
                        ->placeholder('Prevent editing without special permission')
                        ->sendTrueOrFalse()
                        ->canSee($user->hasAccess('platform.blocks.manage_locked')),
                ]),
            ]),

            // Dynamic fields based on block type
            Layout::rows($this->getTypeSpecificFields()),
        ];
    }

    /**
     * Get type-specific fields based on block type.
     */
    protected function getTypeSpecificFields(): array
    {
        $type = $this->block->type ?? request()->input('block.type');

        if (! $type) {
            return [
                Label::make('block._notice')
                    ->title('Next step')
                    ->value('Select a Block Type above to configure this block.'),
            ];
        }

        return match ($type) {
            'hero' => $this->getHeroFields(),
            'richText' => $this->getRichTextField(),
            'image' => $this->getImageFields(),
            'cta' => $this->getCtaFields(),
            'featureGrid' => $this->getFeatureGridFields(),
            'html' => $this->getHtmlFields(),
            default => [],
        };
    }

    /**
     * Hero block fields.
     */
    protected function getHeroFields(): array
    {
        return [
            Input::make('block.data.headline')
                ->title('Headline')
                ->required()
                ->maxlength(255),

            Input::make('block.data.subheadline')
                ->title('Subheadline')
                ->maxlength(500),

            Input::make('block.data.image_url')
                ->title('Image URL')
                ->type('url')
                ->maxlength(2048),

            TextArea::make('block.data.ctas')
                ->title('CTAs (JSON)')
                ->help('JSON array of {label, url} objects')
                ->rows(3),
        ];
    }

    /**
     * Rich text block fields.
     */
    protected function getRichTextField(): array
    {
        return [
            TextArea::make('block.data.markdown')
                ->title('Content (Markdown)')
                ->required()
                ->rows(15)
                ->help('Markdown-formatted content. HTML will be sanitized.'),
        ];
    }

    /**
     * Image block fields.
     */
    protected function getImageFields(): array
    {
        return [
            Input::make('block.data.url')
                ->title('Image URL')
                ->type('url')
                ->required()
                ->maxlength(2048),

            Input::make('block.data.alt')
                ->title('Alt Text')
                ->maxlength(255),

            Input::make('block.data.caption')
                ->title('Caption')
                ->maxlength(500),
        ];
    }

    /**
     * CTA block fields.
     */
    protected function getCtaFields(): array
    {
        return [
            Input::make('block.data.title')
                ->title('Title')
                ->required()
                ->maxlength(255),

            TextArea::make('block.data.body')
                ->title('Body')
                ->maxlength(1000)
                ->rows(3),

            Input::make('block.data.button_label')
                ->title('Button Label')
                ->required()
                ->maxlength(100),

            Input::make('block.data.button_url')
                ->title('Button URL')
                ->type('url')
                ->required()
                ->maxlength(2048),

            Select::make('block.data.variant')
                ->title('Variant')
                ->options([
                    'primary' => 'Primary',
                    'secondary' => 'Secondary',
                ]),
        ];
    }

    /**
     * Feature grid block fields.
     */
    protected function getFeatureGridFields(): array
    {
        return [
            Input::make('block.data.title')
                ->title('Grid Title')
                ->maxlength(255),

            TextArea::make('block.data.items')
                ->title('Items (JSON)')
                ->help('JSON array of {title, body, url} objects')
                ->required()
                ->rows(10),
        ];
    }

    /**
     * HTML block fields (restricted).
     */
    protected function getHtmlFields(): array
    {
        return [
            TextArea::make('block.data.html')
                ->title('Raw HTML')
                ->required()
                ->rows(15)
                ->help('⚠️ WARNING: Raw HTML can be dangerous. Only use trusted content.'),
        ];
    }

    /**
     * Save as draft.
     */
    public function saveDraft(Request $request, Block $block): void
    {
        $this->saveBlock($request, $block, 'draft');
    }

    /**
     * Publish block.
     */
    public function publish(Request $request, Block $block): void
    {
        if (! auth()->user()->hasAccess('platform.blocks.publish')) {
            Toast::error(__('You do not have permission to publish blocks'));

            return;
        }

        $this->saveBlock($request, $block, 'published');
    }

    /**
     * Unpublish block.
     */
    public function unpublish(Request $request, Block $block): void
    {
        if (! $block->exists || ! auth()->user()->hasAccess('platform.blocks.publish')) {
            Toast::error(__('You do not have permission to unpublish blocks'));

            return;
        }

        $block->status = 'draft';
        $block->published_at = null;
        $block->save();

        Toast::success(__('Block unpublished successfully'));
    }

    /**
     * Save block with validation.
     */
    protected function saveBlock(Request $request, Block $block, string $status): void
    {
        // Check locked status
        if ($block->locked && ! auth()->user()->hasAccess('platform.blocks.manage_locked')) {
            Toast::error(__('This block is locked and cannot be edited'));

            return;
        }

        $type = $request->input('block.type');

        // Validate type exists
        if (! BlockTypeRegistry::exists($type)) {
            Toast::error(__('Invalid block type'));

            return;
        }

        // Check HTML permission
        if ($type === 'html' && ! auth()->user()->hasAccess('platform.blocks.manage_html')) {
            Toast::error(__('You do not have permission to manage HTML blocks'));

            return;
        }

        // Basic validation
        $validated = $request->validate([
            'block.key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('blocks', 'key')->ignore($block->id),
            ],
            'block.type' => 'required|string',
            'block.data' => 'required|array',
            'block.visibility' => 'nullable|string',
            'block.locked' => 'nullable|boolean',
        ]);

        // Validate type-specific data
        try {
            $sanitizedData = BlockTypeRegistry::validateData($type, $validated['block']['data']);
            $sanitizedData = BlockTypeRegistry::sanitize($type, $sanitizedData);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $errors) {
                foreach ($errors as $error) {
                    Toast::error($error);
                }
            }

            return;
        }

        // Set block data
        $block->key = $validated['block']['key'];
        $block->type = $type;
        $block->data = $sanitizedData;
        $block->status = $status;

        // Handle visibility
        if (auth()->user()->hasAccess('platform.blocks.manage_visibility')) {
            $block->visibility = $validated['block']['visibility'] ?: null;
        }

        // Handle locked
        if (auth()->user()->hasAccess('platform.blocks.manage_locked')) {
            $block->locked = $validated['block']['locked'] ?? false;
        }

        // Set audit fields
        if (! $block->exists) {
            $block->created_by = auth()->id();
        }
        $block->updated_by = auth()->id();

        $block->save();

        // Clear cache for this block
        \Illuminate\Support\Facades\Cache::forget("block:{$block->key}");

        Toast::success(__('Block saved successfully'));
    }

    /**
     * Remove block.
     */
    public function remove(Block $block): void
    {
        if (! auth()->user()->hasAccess('platform.blocks.delete')) {
            Toast::error(__('You do not have permission to delete blocks'));

            return;
        }

        if ($block->locked && ! auth()->user()->hasAccess('platform.blocks.manage_locked')) {
            Toast::error(__('This block is locked and cannot be deleted'));

            return;
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("block:{$block->key}");

        $block->delete();

        Toast::success(__('Block deleted successfully'));
    }
}
