<?php

namespace App\Models;

use Core\Database\Model;
use Core\Database\Relations\BelongsTo;

/**
 * Sample Post model for testing relationships.
 */
class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [
        'user_id',
        'title',
        'content',
    ];

    /**
     * A post belongs to a user.
     */
    public function user(): BelongsTo
    {
        return new BelongsTo($this, User::class);
    }
}
