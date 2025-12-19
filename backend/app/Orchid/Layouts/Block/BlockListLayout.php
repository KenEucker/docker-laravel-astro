<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Block;

use App\Models\Block;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class BlockListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    public $target = 'blocks';

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
                ->render(fn (Block $block) => Link::make($block->key)
                    ->route('platform.blocks.edit', $block)),

            TD::make('type', 'Type')
                ->sort()
                ->filter(Select::make()->options([
                    'hero' => 'Hero',
                    'richText' => 'Rich Text',
                    'image' => 'Image',
                    'cta' => 'CTA',
                    'featureGrid' => 'Feature Grid',
                    'html' => 'HTML',
                ])->empty('All Types'))
                ->render(fn (Block $block) => ucfirst($block->type)),

            TD::make('status', 'Status')
                ->sort()
                ->filter(Select::make()->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])->empty('All Statuses'))
                ->render(function (Block $block) {
                    $color = $block->status === 'published' ? 'success' : 'secondary';
                    return "<span class=\"badge bg-{$color}\">" . ucfirst($block->status) . "</span>";
                }),

            TD::make('visibility', 'Visibility')
                ->render(fn (Block $block) => $block->visibility ?? 'Public'),

            TD::make('locked', 'Locked')
                ->render(fn (Block $block) => $block->locked
                    ? '<span class="badge bg-warning">Locked</span>'
                    : ''),

            TD::make('updated_at', 'Updated')
                ->sort()
                ->render(fn (Block $block) => $block->updated_at->format('M d, Y H:i')),

            TD::make('actions', 'Actions')
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (Block $block) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->icon('bs.pencil')
                            ->route('platform.blocks.edit', $block)
                            ->canSee(auth()->user()->hasAccess('platform.blocks.edit')),

                        Button::make(__('Delete'))
                            ->icon('bs.trash')
                            ->method('remove')
                            ->confirm(__('Are you sure you want to delete this block?'))
                            ->parameters(['id' => $block->id])
                            ->canSee(
                                auth()->user()->hasAccess('platform.blocks.delete') &&
                                (!$block->locked || auth()->user()->hasAccess('platform.blocks.manage_locked'))
                            ),
                    ])),
        ];
    }
}
