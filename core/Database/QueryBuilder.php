<?php

namespace Core\Database;

use Core\Application\DB;
use Core\Helpers\Log;
use Core\Database\Exceptions\QueryException;
use PDO;

/**
 * Lightweight query builder for MySQL.
 * 
 * Provides a fluent interface for building and executing SQL queries.
 * All values are bound as parameters using prepared statements.
 */
class QueryBuilder
{
    private string $table = '';
    private array $select = ['*'];
    private array $wheres = [];
    private array $joins = [];
    private array $bindings = [];
    private array $orderBys = [];
    private array $groupBys = [];
    private array $havings = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private string $whereOperator = 'AND';
    private float $startTime = 0;

    /**
     * Create a new query builder instance.
     *
     * @param string $table The table name
     */
    public function __construct(string $table)
    {
        $this->table = $this->quoteIdentifier($table);
    }

    /**
     * Specify which columns to select.
     *
     * @param array<string> $columns Column names
     * @return self
     */
    public function select(array $columns): self
    {
        $this->select = array_map(fn($col) => $this->quoteIdentifier($col), $columns);
        return $this;
    }

    /**
     * Add additional columns to the selection.
     *
     * @param array<string> $columns Column names
     * @return self
     */
    public function addSelect(array $columns): self
    {
        foreach ($columns as $column) {
            $this->select[] = $this->quoteIdentifier($column);
        }
        return $this;
    }

    /**
     * Add a WHERE clause.
     *
     * @param string $column Column name
     * @param mixed $operator Comparison operator or value
     * @param mixed $value Value (if operator provided)
     * @return self
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        // Handle overloaded parameters: where($col, $value) or where($col, $op, $value)
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'Basic',
            'column' => $this->quoteIdentifier($column),
            'operator' => $operator ?? '=',
            'value' => $value,
            'boolean' => 'AND',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add an OR WHERE clause.
     *
     * @param string $column Column name
     * @param mixed $operator Comparison operator or value
     * @param mixed $value Value (if operator provided)
     * @return self
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'Basic',
            'column' => $this->quoteIdentifier($column),
            'operator' => $operator ?? '=',
            'value' => $value,
            'boolean' => 'OR',
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add a WHERE IN clause.
     *
     * @param string $column Column name
     * @param array<mixed> $values Values to check
     * @return self
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            // Handle empty array - no results will match
            $this->wheres[] = [
                'type' => 'Raw',
                'sql' => '0=1',
                'boolean' => 'AND',
            ];
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));

        $this->wheres[] = [
            'type' => 'Raw',
            'sql' => "{$this->quoteIdentifier($column)} IN ({$placeholders})",
            'boolean' => 'AND',
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add a WHERE NULL clause.
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'Null',
            'column' => $this->quoteIdentifier($column),
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause.
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'NotNull',
            'column' => $this->quoteIdentifier($column),
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Add a JOIN clause.
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'inner',
            'table' => $this->quoteIdentifier($table),
            'first' => $this->quoteIdentifier($first),
            'operator' => $operator,
            'second' => $this->quoteIdentifier($second),
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'left',
            'table' => $this->quoteIdentifier($table),
            'first' => $this->quoteIdentifier($first),
            'operator' => $operator,
            'second' => $this->quoteIdentifier($second),
        ];

        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column Column name
     * @param string $direction ASC or DESC
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        $this->orderBys[] = "{$this->quoteIdentifier($column)} {$direction}";

        return $this;
    }

    /**
     * Add a GROUP BY clause.
     *
     * @param array<string> $columns Column names
     * @return self
     */
    public function groupBy(array $columns): self
    {
        foreach ($columns as $column) {
            $this->groupBys[] = $this->quoteIdentifier($column);
        }

        return $this;
    }

    /**
     * Add a HAVING clause.
     *
     * @param string $column Column name
     * @param string $operator Comparison operator
     * @param mixed $value Value
     * @return self
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->havings[] = [
            'column' => $this->quoteIdentifier($column),
            'operator' => $operator,
            'value' => $value,
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Set the LIMIT clause.
     *
     * @param int $limit Number of rows
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Set the OFFSET clause.
     *
     * @param int $offset Number of rows to skip
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Execute the query and return all results.
     *
     * @return array<array<string, mixed>>
     * @throws QueryException
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->executeQuery($sql, 'SELECT', true);
    }

    /**
     * Execute the query and return the first result.
     *
     * @return ?array<string, mixed>
     * @throws QueryException
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Find a record by primary key.
     *
     * @param int|string $id The primary key value
     * @return ?array<string, mixed>
     * @throws QueryException
     */
    public function find(int|string $id): ?array
    {
        return $this->where('id', '=', $id)->first();
    }

    /**
     * Insert a record and return the insert ID.
     *
     * @param array<string, mixed> $data Column => value pairs
     * @return int|string The last insert ID
     * @throws QueryException
     */
    public function insert(array $data): int|string
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $quotedColumns = implode(',', array_map(fn($col) => $this->quoteIdentifier($col), $columns));

        $sql = "INSERT INTO {$this->table} ({$quotedColumns}) VALUES ({$placeholders})";

        return $this->executeInsert($sql, $values);
    }

    /**
     * Update records and return the number of affected rows.
     *
     * @param array<string, mixed> $data Column => value pairs
     * @return int Number of affected rows
     * @throws QueryException
     */
    public function update(array $data): int
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = $this->quoteIdentifier($column) . ' = ?';
            $values[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(',', $sets);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        // Combine update values with where bindings
        $bindings = array_merge($values, $this->bindings);

        return $this->executeUpdate($sql, $bindings);
    }

    /**
     * Delete records and return the number of affected rows.
     *
     * @return int Number of affected rows
     * @throws QueryException
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        return $this->executeDelete($sql, $this->bindings);
    }

    /**
     * Count the number of records.
     *
     * @return int
     * @throws QueryException
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        $result = $this->executeQuery($sql, 'SELECT', true);
        
        return count($result) > 0 ? (int) $result[0]['count'] : 0;
    }

    /**
     * Check if any records exist matching the query.
     *
     * @return bool
     * @throws QueryException
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Build the SELECT query string.
     *
     * @return string
     */
    private function buildSelectQuery(): string
    {
        $sql = 'SELECT ' . implode(',', $this->select) . " FROM {$this->table}";

        // Add joins
        foreach ($this->joins as $join) {
            $type = strtoupper($join['type']) . ' JOIN';
            $sql .= " {$type} {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Add where clause
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        // Add group by
        if (!empty($this->groupBys)) {
            $sql .= ' GROUP BY ' . implode(',', $this->groupBys);
        }

        // Add having
        if (!empty($this->havings)) {
            $having = [];
            foreach ($this->havings as $h) {
                $having[] = "{$h['column']} {$h['operator']} ?";
            }
            $sql .= ' HAVING ' . implode(' AND ', $having);
        }

        // Add order by
        if (!empty($this->orderBys)) {
            $sql .= ' ORDER BY ' . implode(',', $this->orderBys);
        }

        // Add limit
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . (int) $this->limitValue;
        }

        // Add offset
        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . (int) $this->offsetValue;
        }

        return $sql;
    }

    /**
     * Build the WHERE clause string.
     *
     * @return string
     */
    private function buildWhereClause(): string
    {
        $conditions = [];

        foreach ($this->wheres as $where) {
            $boolean = $where['boolean'];

            if ($where['type'] === 'Basic') {
                $conditions[] = "{$boolean} {$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'Raw') {
                $conditions[] = "{$boolean} {$where['sql']}";
            } elseif ($where['type'] === 'Null') {
                $conditions[] = "{$boolean} {$where['column']} IS NULL";
            } elseif ($where['type'] === 'NotNull') {
                $conditions[] = "{$boolean} {$where['column']} IS NOT NULL";
            }
        }

        // Remove leading boolean from first condition
        $clause = implode(' ', $conditions);
        return preg_replace('/^(AND|OR)\s+/', '', $clause);
    }

    /**
     * Execute a SELECT query.
     *
     * @param string $sql
     * @param string $type Query type for logging
     * @param bool $logQuery Whether to log the query
     * @return array<array<string, mixed>>
     * @throws QueryException
     */
    private function executeQuery(string $sql, string $type, bool $logQuery = true): array
    {
        $this->startTime = microtime(true);

        try {
            $result = DB::query($sql, $this->bindings);
            
            $time = (microtime(true) - $this->startTime) * 1000;

            if ($logQuery) {
                Log::info("Query executed: {$type}", [
                    'query' => $sql,
                    'time_ms' => round($time, 2),
                    'rows' => count($result),
                ], 'database');
            }

            return $result ?? [];
        } catch (\Throwable $e) {
            Log::error("Query failed: {$type}", [
                'query' => $sql,
                'error' => $e->getMessage(),
            ], 'errors');

            throw new QueryException("Query failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Execute an INSERT query.
     *
     * @param string $sql
     * @param array<mixed> $bindings
     * @return int|string
     * @throws QueryException
     */
    private function executeInsert(string $sql, array $bindings): int|string
    {
        $this->startTime = microtime(true);

        try {
            // We need to use prepared statements via PDO directly
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $time = (microtime(true) - $this->startTime) * 1000;

            Log::info("INSERT executed", [
                'query' => $sql,
                'time_ms' => round($time, 2),
            ], 'database');

            return $pdo->lastInsertId();
        } catch (\Throwable $e) {
            Log::error("INSERT failed", [
                'query' => $sql,
                'error' => $e->getMessage(),
            ], 'errors');

            throw new QueryException("INSERT failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Execute an UPDATE query.
     *
     * @param string $sql
     * @param array<mixed> $bindings
     * @return int
     * @throws QueryException
     */
    private function executeUpdate(string $sql, array $bindings): int
    {
        $this->startTime = microtime(true);

        try {
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $time = (microtime(true) - $this->startTime) * 1000;
            $rows = $stmt->rowCount();

            Log::info("UPDATE executed", [
                'query' => $sql,
                'time_ms' => round($time, 2),
                'rows' => $rows,
            ], 'database');

            return $rows;
        } catch (\Throwable $e) {
            Log::error("UPDATE failed", [
                'query' => $sql,
                'error' => $e->getMessage(),
            ], 'errors');

            throw new QueryException("UPDATE failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Execute a DELETE query.
     *
     * @param string $sql
     * @param array<mixed> $bindings
     * @return int
     * @throws QueryException
     */
    private function executeDelete(string $sql, array $bindings): int
    {
        $this->startTime = microtime(true);

        try {
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $time = (microtime(true) - $this->startTime) * 1000;
            $rows = $stmt->rowCount();

            Log::info("DELETE executed", [
                'query' => $sql,
                'time_ms' => round($time, 2),
                'rows' => $rows,
            ], 'database');

            return $rows;
        } catch (\Throwable $e) {
            Log::error("DELETE failed", [
                'query' => $sql,
                'error' => $e->getMessage(),
            ], 'errors');

            throw new QueryException("DELETE failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Safely quote an identifier (table or column name).
     *
     * @param string $identifier
     * @return string
     */
    private function quoteIdentifier(string $identifier): string
    {
        // Don't quote if already quoted or contains special characters
        if (strpos($identifier, '`') !== false || strpos($identifier, '*') !== false) {
            return $identifier;
        }

        // Quote each part separately for namespaced identifiers
        $parts = explode('.', $identifier);
        return implode('.', array_map(fn($part) => "`{$part}`", $parts));
    }
}
