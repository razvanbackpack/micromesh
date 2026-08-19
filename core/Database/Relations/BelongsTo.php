<?php

namespace Core\Database\Relations;

use Core\Database\Model;
use Core\Application\DB;

/**
 * Represents a many-to-one relationship.
 * 
 * A Post belongsTo a User.
 */
class BelongsTo
{
    private Model $child;
    private string $related;
    private string $foreignKey;
    private string $ownerKey;

    /**
     * Create a new BelongsTo relationship.
     * 
     * @param Model $child The child model instance
     * @param string $related The related (parent) model class name
     * @param string $foreignKey The foreign key on the child model
     * @param string $ownerKey The key on the parent model
     */
    public function __construct(
        Model $child,
        string $related,
        string $foreignKey = '',
        string $ownerKey = 'id'
    ) {
        $this->child = $child;
        $this->related = $related;
        $this->foreignKey = $foreignKey ?: $this->guessRelation() . '_id';
        $this->ownerKey = $ownerKey;
    }

    /**
     * Get the related parent model.
     * 
     * @return Model|null
     */
    public function get(): ?Model
    {
        $parentModel = new $this->related();
        $foreignKeyValue = $this->child->getAttribute($this->foreignKey);

        if ($foreignKeyValue === null) {
            return null;
        }

        $result = DB::table($parentModel->getTable())
            ->where($this->ownerKey, '=', $foreignKeyValue)
            ->first();

        if ($result === null) {
            return null;
        }

        $instance = new $this->related();
        $instance->setRawAttributes($result);
        return $instance;
    }

    /**
     * Guess the relationship name from related model.
     * 
     * @return string
     */
    private function guessRelation(): string
    {
        $related = class_basename($this->related);
        return strtolower($related);
    }
}
