<?php

namespace Core\Database\Relations;

use Core\Database\Model;
use Core\Database\QueryBuilder;
use Core\Application\DB;

/**
 * Represents a one-to-many relationship.
 * 
 * A User hasMany Posts.
 */
class HasMany
{
    private Model $parent;
    private string $related;
    private string $foreignKey;
    private string $localKey;

    /**
     * Create a new HasMany relationship.
     * 
     * @param Model $parent The parent model instance
     * @param string $related The related model class name
     * @param string $foreignKey The foreign key on the related model
     * @param string $localKey The local key on the parent model
     */
    public function __construct(
        Model $parent,
        string $related,
        string $foreignKey = '',
        string $localKey = 'id'
    ) {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey ?: $this->guessRelation() . '_id';
        $this->localKey = $localKey;
    }

    /**
     * Get all related models.
     * 
     * @return array<Model>
     */
    public function get(): array
    {
        $relatedModel = new $this->related();
        $results = DB::table($relatedModel->getTable())
            ->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey))
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
        $result = DB::table($relatedModel->getTable())
            ->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey))
            ->first();

        if ($result === null) {
            return null;
        }

        $instance = new $this->related();
        $instance->setRawAttributes($result);
        return $instance;
    }

    /**
     * Get a query builder for related models.
     * 
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        $relatedModel = new $this->related();
        return DB::table($relatedModel->getTable())
            ->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey));
    }

    /**
     * Guess the relationship name from parent model.
     * 
     * @return string
     */
    private function guessRelation(): string
    {
        $parent = class_basename(get_class($this->parent));
        return strtolower($parent);
    }

    /**
     * Set raw attributes on the related model for accessing properties directly.
     * 
     * @param array $attributes
     * @return void
     */
    public function setRawAttributes(array $attributes): void
    {
        $relatedModel = new $this->related();
        $relatedModel->setRawAttributes($attributes);
    }
}
