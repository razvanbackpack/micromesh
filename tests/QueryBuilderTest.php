<?php

namespace Tests;

use Core\Application\DB;
use Core\Database\QueryBuilder;

/**
 * Test suite for QueryBuilder.
 * 
 * Note: These tests require a database connection configured in .env
 * and assume a 'test_users' table with the following structure:
 * 
 * CREATE TABLE test_users (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   name VARCHAR(255) NOT NULL,
 *   email VARCHAR(255) UNIQUE NOT NULL,
 *   active BOOLEAN DEFAULT true
 * );
 */
class QueryBuilderTest
{
    /**
     * Run all tests.
     */
    public function runAll(): void
    {
        echo "=== Query Builder Tests ===\n\n";

        // Test 1: Basic table reference
        try {
            $this->testTableReference();
            echo "✓ Table reference test passed\n";
        } catch (Throwable $e) {
            echo "✗ Table reference test failed: {$e->getMessage()}\n";
        }

        // Test 2: Select columns
        try {
            $this->testSelectColumns();
            echo "✓ Select columns test passed\n";
        } catch (Throwable $e) {
            echo "✗ Select columns test failed: {$e->getMessage()}\n";
        }

        // Test 3: Where clause
        try {
            $this->testWhereClause();
            echo "✓ Where clause test passed\n";
        } catch (Throwable $e) {
            echo "✗ Where clause test failed: {$e->getMessage()}\n";
        }

        // Test 4: Order by
        try {
            $this->testOrderBy();
            echo "✓ Order by test passed\n";
        } catch (Throwable $e) {
            echo "✗ Order by test failed: {$e->getMessage()}\n";
        }

        // Test 5: Limit and offset
        try {
            $this->testLimitOffset();
            echo "✓ Limit and offset test passed\n";
        } catch (Throwable $e) {
            echo "✗ Limit and offset test failed: {$e->getMessage()}\n";
        }

        echo "\n=== Query Builder Tests Complete ===\n";
    }

    private function testTableReference(): void
    {
        $builder = DB::table('test_users');
        
        if (!($builder instanceof QueryBuilder)) {
            throw new \Exception("DB::table() should return QueryBuilder instance");
        }
    }

    private function testSelectColumns(): void
    {
        $builder = DB::table('test_users')
            ->select(['id', 'email']);
        
        if (!($builder instanceof QueryBuilder)) {
            throw new \Exception("Select should return chainable QueryBuilder");
        }
    }

    private function testWhereClause(): void
    {
        $builder = DB::table('test_users')
            ->where('active', '=', true)
            ->where('id', '>', 1);
        
        if (!($builder instanceof QueryBuilder)) {
            throw new \Exception("Where clause should return chainable QueryBuilder");
        }
    }

    private function testOrderBy(): void
    {
        $builder = DB::table('test_users')
            ->orderBy('created_at', 'DESC')
            ->orderBy('name', 'ASC');
        
        if (!($builder instanceof QueryBuilder)) {
            throw new \Exception("OrderBy should return chainable QueryBuilder");
        }
    }

    private function testLimitOffset(): void
    {
        $builder = DB::table('test_users')
            ->limit(10)
            ->offset(5);
        
        if (!($builder instanceof QueryBuilder)) {
            throw new \Exception("Limit/offset should return chainable QueryBuilder");
        }
    }
}
