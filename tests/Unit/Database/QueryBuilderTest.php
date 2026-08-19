<?php

namespace Tests\Unit\Database;

use Core\Application\DB;
use Core\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testTableCreatesQueryBuilder(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, DB::table('test_users'));
    }

    public function testSelectAndWhereAreChainable(): void
    {
        $query = DB::table('test_users')
            ->select(['id', 'email'])
            ->where('active', true)
            ->where('id', '>', 1);

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testOrderLimitAndOffsetAreChainable(): void
    {
        $query = DB::table('test_users')
            ->orderBy('created_at', 'DESC')
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->offset(5);

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testWhereInAndNullConditionsAreChainable(): void
    {
        $query = DB::table('test_users')
            ->whereIn('id', [1, 2, 3])
            ->whereNull('deleted_at')
            ->whereNotNull('email');

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testGroupByAndHavingAreChainable(): void
    {
        $query = DB::table('test_users')
            ->groupBy(['active'])
            ->having('active', '=', 1);

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testFindAndFirstMethodsAreAvailable(): void
    {
        $query = DB::table('test_users');

        $this->assertTrue(method_exists($query, 'find'));
        $this->assertTrue(method_exists($query, 'first'));
    }
}
