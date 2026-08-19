<?php

namespace App\Models;

use Core\Database\Model;
use Core\Database\Relations\HasMany;

/**
 * Sample User model for testing.
 */
class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'active',
    ];

    protected array $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
