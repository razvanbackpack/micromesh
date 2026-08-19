<?php

namespace Tests;

use App\Models\User;

/**
 * Test suite for Model ORM.
 * 
 * Note: These tests require a database connection and users table.
 */
class ModelTest
{
    public function runAll(): void
    {
        echo "=== Model ORM Tests ===\n\n";

        try {
            $this->testModelInstantiation();
            echo "✓ Model instantiation test passed\n";
        } catch (Throwable $e) {
            echo "✗ Model instantiation test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testMassAssignment();
            echo "✓ Mass assignment test passed\n";
        } catch (Throwable $e) {
            echo "✗ Mass assignment test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testAttributeCasting();
            echo "✓ Attribute casting test passed\n";
        } catch (Throwable $e) {
            echo "✗ Attribute casting test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testArrayConversion();
            echo "✓ Array conversion test passed\n";
        } catch (Throwable $e) {
            echo "✗ Array conversion test failed: {$e->getMessage()}\n";
        }

        echo "\n=== Model Tests Complete ===\n";
    }

    private function testModelInstantiation(): void
    {
        $user = new User();
        
        if (!($user instanceof User)) {
            throw new \Exception("Failed to instantiate User model");
        }

        if ($user->getTable() !== 'users') {
            throw new \Exception("Table name should be 'users'");
        }
    }

    private function testMassAssignment(): void
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
        ]);

        if ($user->name !== 'John Doe') {
            throw new \Exception("Mass assignment failed for name");
        }

        if ($user->email !== 'john@example.com') {
            throw new \Exception("Mass assignment failed for email");
        }
    }

    private function testAttributeCasting(): void
    {
        $user = new User([
            'active' => '1',
        ]);

        $active = $user->active;
        
        if (!is_bool($active)) {
            throw new \Exception("Attribute casting to boolean failed");
        }

        if ($active !== true) {
            throw new \Exception("Casted value should be true");
        }
    }

    private function testArrayConversion(): void
    {
        $user = new User([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $array = $user->toArray();

        if (!is_array($array)) {
            throw new \Exception("toArray() should return an array");
        }

        if ($array['name'] !== 'Jane Doe') {
            throw new \Exception("Array conversion failed");
        }
    }
}
