<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Block;

use App\Models\Block;
use App\Orchid\Layouts\Block\BlockListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class BlockListScreen extends Screen
{
    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Content Blocks';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Manage content blocks for the frontend';
    }

    /**
     * Permission name.
     */
    public function permission(): ?iterable
    {
        return [
            'platform.blocks.view',
        ];
    }

    /**
     * Query data.
     */
    public function query(): iterable
    {
        return [
            'blocks' => Block::filters()
                ->defaultSort('updated_at', 'desc')
                ->paginate(20),
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create Block'))
                ->icon('bs.plus-circle')
                ->route('platform.blocks.create')
                ->canSee(auth()->user()->hasAccess('platform.blocks.create')),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            BlockListLayout::class,
        ];
    }

    /**
     * Remove a block.
     */
    public function remove(Request $request): void
    {
        $block = Block::findOrFail($request->get('id'));

        // Check permissions
        if (!auth()->user()->hasAccess('platform.blocks.delete')) {
            Toast::error(__('You do not have permission to delete blocks'));
            return;
        }

        if ($block->locked && !auth()->user()->hasAccess('platform.blocks.manage_locked')) {
            Toast::error(__('This block is locked and cannot be deleted'));
            return;
        }

        $block->delete();

        Toast::success(__('Block deleted successfully'));
    }
}
