<?php

namespace Core\Database\Relations;

use Core\Database\Model;
use Core\Application\DB;

/**
 * Represents a many-to-many relationship.
 * 
 * A User belongsToMany Roles (through a pivot table).
 */
class BelongsToMany
{
    private Model $parent;
    private string $related;
    private string $table;
    private string $foreignKey;
    private string $relatedKey;
    private string $parentKey;
    private string $relatedParentKey;

    /**
     * Create a new BelongsToMany relationship.
     * 
     * @param Model $parent The parent model instance
     * @param string $related The related model class name
     * @param string $table The pivot table name
     * @param string $foreignKey The foreign key on the pivot table for the parent
     * @param string $relatedKey The foreign key on the pivot table for the related
     * @param string $parentKey The key on the parent model
     * @param string $relatedParentKey The key on the related model
     */
    public function __construct(
        Model $parent,
        string $related,
        string $table = '',
        string $foreignKey = '',
        string $relatedKey = '',
        string $parentKey = 'id',
        string $relatedParentKey = 'id'
    ) {
        $this->parent = $parent;
        $this->related = $related;
        
        // Generate table name from sorted model names if not provided
        if (empty($table)) {
            $parentClass = strtolower(class_basename(get_class($parent)));
            $relatedClass = strtolower(class_basename($related));
            $names = [$parentClass, $relatedClass];
            sort($names);
            $table = implode('_', $names);
        }
        
        $this->table = $table;
        $this->foreignKey = $foreignKey ?: strtolower(class_basename(get_class($parent))) . '_id';
        $this->relatedKey = $relatedKey ?: strtolower(class_basename($related)) . '_id';
        $this->parentKey = $parentKey;
        $this->relatedParentKey = $relatedParentKey;
    }

    /**
     * Get all related models.
     * 
     * @return array<Model>
     */
    public function get(): array
    {
        $relatedModel = new $this->related();
        
        $results = DB::table($this->table)
            ->select(["{$this->table}.*", "{$relatedModel->getTable()}.*"])
            ->join(
                $relatedModel->getTable(),
                "{$this->table}.{$this->relatedKey}",
                '=',
                "{$relatedModel->getTable()}.{$this->relatedParentKey}"
            )
            ->where("{$this->table}.{$this->foreignKey}", '=', $this->parent->getAttribute($this->parentKey))
            ->get();

        return array_map(function($result) {
            $instance = new $this->related();
            $instance->setRawAttributes($result);
            return $instance;
        }, $results);
    }

    /**
     * Get the first related model.
     * 
     * @return Model|null
     */
    public function first(): ?Model
    {
        $relatedModel = new $this->related();
        
        $result = DB::table($this->table)
            ->select(["{$this->table}.*", "{$relatedModel->getTable()}.*"])
            ->join(
                $relatedModel->getTable(),
                "{$this->table}.{$this->relatedKey}",
                '=',
                "{$relatedModel->getTable()}.{$this->relatedParentKey}"
            )
            ->where("{$this->table}.{$this->foreignKey}", '=', $this->parent->getAttribute($this->parentKey))
            ->first();

        if ($result === null) {
            return null;
        }

        $instance = new $this->related();
        $instance->setRawAttributes($result);
        return $instance;
    }

    /**
     * Attach a related model (add to pivot table).
     * 
     * @param int|string $id The ID of the related model
     * @param array<string, mixed> $attributes Additional attributes for the pivot
     * @return bool
     */
    public function attach(int|string $id, array $attributes = []): bool
    {
        $data = [
            $this->foreignKey => $this->parent->getAttribute($this->parentKey),
            $this->relatedKey => $id,
            ...$attributes
        ];

        $result = DB::table($this->table)->insert($data);
        return (int) $result > 0;
    }

    /**
     * Detach a related model (remove from pivot table).
     * 
     * @param int|string $id The ID of the related model
     * @return int Number of rows affected
     */
    public function detach(int|string $id): int
    {
        return DB::table($this->table)
            ->where($this->foreignKey, '=', $this->parent->getAttribute($this->parentKey))
            ->where($this->relatedKey, '=', $id)
            ->delete();
    }

    /**
     * Detach all related models.
     * 
     * @return int Number of rows affected
     */
    public function detachAll(): int
    {
        return DB::table($this->table)
            ->where($this->foreignKey, '=', $this->parent->getAttribute($this->parentKey))
            ->delete();
    }
}
