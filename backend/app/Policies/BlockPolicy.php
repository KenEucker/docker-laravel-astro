<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;
use App\Services\BlockTypeRegistry;

class BlockPolicy
{
    /**
     * Determine if the user can view any blocks.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAccess('platform.blocks.view');
    }

    /**
     * Determine if the user can view the block.
     */
    public function view(User $user, Block $block): bool
    {
        return $user->hasAccess('platform.blocks.view');
    }

    /**
     * Determine if the user can create blocks.
     */
    public function create(User $user): bool
    {
        return $user->hasAccess('platform.blocks.create');
    }

    /**
     * Determine if the user can create a block of a specific type.
     * HTML blocks require special permission.
     */
    public function createType(User $user, string $type): bool
    {
        if (! $user->hasAccess('platform.blocks.create')) {
            return false;
        }

        // Check if type is restricted (e.g., html)
        if (BlockTypeRegistry::isRestricted($type)) {
            return $user->hasAccess('platform.blocks.manage_html');
        }

        return true;
    }

    /**
     * Determine if the user can update the block.
     */
    public function update(User $user, Block $block): bool
    {
        if (! $user->hasAccess('platform.blocks.edit')) {
            return false;
        }

        // Check if block is locked
        if ($block->locked && ! $user->hasAccess('platform.blocks.manage_locked')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can change the block type.
     * Type changes require special permission since they can invalidate data.
     */
    public function changeType(User $user, Block $block, string $newType): bool
    {
        if (! $this->update($user, $block)) {
            return false;
        }

        // Changing to a restricted type requires special permission
        if (BlockTypeRegistry::isRestricted($newType)) {
            return $user->hasAccess('platform.blocks.manage_html');
        }

        return true;
    }

    /**
     * Determine if the user can publish the block.
     */
    public function publish(User $user, Block $block): bool
    {
        if (! $user->hasAccess('platform.blocks.publish')) {
            return false;
        }

        // Check if block is locked
        if ($block->locked && ! $user->hasAccess('platform.blocks.manage_locked')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can change the block's visibility.
     */
    public function changeVisibility(User $user, Block $block): bool
    {
        return $user->hasAccess('platform.blocks.manage_visibility');
    }

    /**
     * Determine if the user can lock/unlock the block.
     */
    public function changeLocked(User $user, Block $block): bool
    {
        return $user->hasAccess('platform.blocks.manage_locked');
    }

    /**
     * Determine if the user can delete the block.
     */
    public function delete(User $user, Block $block): bool
    {
        if (! $user->hasAccess('platform.blocks.delete')) {
            return false;
        }

        // Check if block is locked
        if ($block->locked && ! $user->hasAccess('platform.blocks.manage_locked')) {
            return false;
        }

        return true;
    }
}
