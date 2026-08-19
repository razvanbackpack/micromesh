<?php

namespace App\Repositories;

/**
 * Sample user repository for testing DI.
 */
class UserRepository
{
    public function all(): array
    {
        return [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ];
    }

    public function find(int $id): ?array
    {
        $users = $this->all();
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }

    public function create(array $data): array
    {
        return ['id' => 3, ...$data];
    }
}
