<?php

namespace Core\Database;

use Core\Application\DB;
use Core\Database\Exceptions\ModelException;
use DateTime;
use Throwable;

/**
 * Base Model class for ORM functionality.
 * 
 * Provides a simple interface for database queries, mass assignment,
 * attribute casting, and timestamps.
 * 
 * Usage:
 *   class User extends Model {
 *       protected string $table = 'users';
 *       protected array $fillable = ['name', 'email'];
 *       protected array $casts = ['active' => 'boolean'];
 *   }
 *   
 *   $user = User::find(1);
 *   $users = User::where('active', true)->get();
 *   $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
 */
abstract class Model
{
    /**
     * The table associated with this model.
     */
    protected string $table;

    /**
     * The primary key for the model.
     */
    protected string $primaryKey = 'id';

    /**
     * Attributes that are mass assignable.
     * 
     * @var array<string>
     */
    protected array $fillable = [];

    /**
     * Attributes that are NOT mass assignable.
     * 
     * @var array<string>
     */
    protected array $guarded = [];

    /**
     * Type casting rules for attributes.
     * 
     * Supported types: 'integer', 'boolean', 'float', 'array', 'json', 'datetime'
     * 
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Whether to use timestamps.
     */
    protected bool $timestamps = true;

    /**
     * The attributes that should be hidden from arrays.
     * 
     * @var array<string>
     */
    protected array $hidden = [];

    /**
     * The model's attributes.
     * 
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * The original attribute values.
     * 
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * Tracks whether this is a new model (not yet saved).
     */
    protected bool $exists = false;

    /**
     * Create a new model instance.
     * 
     * @param array<string, mixed> $attributes Initial attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill the model with an array of attributes.
     * 
     * Mass-assignable protection is applied based on fillable/guarded.
     * 
     * @param array<string, mixed> $attributes
     * @return self
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Determine if an attribute is mass-assignable.
     * 
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        // If fillable is empty and guarded is empty, allow all
        if (empty($this->fillable) && empty($this->guarded)) {
            return true;
        }

        // If fillable is defined, only those attributes are fillable
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable, true);
        }

        // If guarded is defined, those attributes are NOT fillable
        return !in_array($key, $this->guarded, true);
    }

    /**
     * Set an attribute on the model.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get an attribute from the model.
     * 
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }

        $value = $this->attributes[$key];

        // Apply casting if defined
        if (isset($this->casts[$key])) {
            $value = $this->castValue($key, $value);
        }

        return $value;
    }

    /**
     * Cast a value based on the cast type.
     * 
     * @param string $key Attribute name
     * @param mixed $value The value to cast
     * @return mixed The casted value
     */
    private function castValue(string $key, mixed $value): mixed
    {
        $castType = $this->casts[$key];

        return match ($castType) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'float' => (float) $value,
            'array' => is_array($value) ? $value : json_decode($value, true) ?? [],
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => $value instanceof DateTime ? $value : new DateTime($value),
            default => $value,
        };
    }

    /**
     * Handle dynamic attribute access.
     * 
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Handle dynamic attribute setting.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Handle dynamic attribute checking.
     * 
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Get the table name for this model.
     * 
     * @return string
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        // Derive table name from class name
        $className = class_basename(static::class);
        return strtolower($className) . 's'; // e.g., User -> users
    }

    /**
     * Get the primary key name.
     * 
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the primary key value.
     * 
     * @return int|string|null
     */
    public function getKey(): int|string|null
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get all attributes as an array.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden, true)) {
                $data[$key] = $this->getAttribute($key);
            }
        }

        return $data;
    }

    /**
     * Save the model to the database.
     * 
     * Creates a new record or updates an existing one.
     * 
     * @return bool True if successful
     * @throws ModelException
     */
    public function save(): bool
    {
        try {
            if ($this->exists) {
                // Update existing record
                $data = array_diff_assoc($this->attributes, $this->original);

                // Always update timestamps if enabled
                if ($this->timestamps && count($data) > 0) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                }

                if (count($data) === 0) {
                    return false;
                }

                $key = $this->getKey();
                $builder = DB::table($this->getTable());
                $builder->where($this->getKeyName(), '=', $key);
                $result = $builder->update($data);

                return $result > 0;
            } else {
                // Create new record
                $data = $this->attributes;

                // Set timestamps if enabled
                if ($this->timestamps) {
                    $now = date('Y-m-d H:i:s');
                    $data['created_at'] = $now;
                    $data['updated_at'] = $now;
                }

                $id = DB::table($this->getTable())->insert($data);
                
                $this->setAttribute($this->getKeyName(), $id);
                $this->exists = true;
                $this->original = $this->attributes;

                return true;
            }
        } catch (Throwable $e) {
            throw new ModelException("Failed to save model: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Delete the model from the database.
     * 
     * @return bool True if successful
     * @throws ModelException
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        try {
            $key = $this->getKey();
            $builder = DB::table($this->getTable());
            $builder->where($this->getKeyName(), '=', $key);
            $result = $builder->delete();

            return $result > 0;
        } catch (Throwable $e) {
            throw new ModelException("Failed to delete model: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Find a model by its primary key.
     * 
     * @param int|string $id
     * @return static|null
     * @throws ModelException
     */
    public static function find(int|string $id): ?static
    {
        try {
            $model = new static();
            $result = DB::table($model->getTable())
                ->where($model->getKeyName(), '=', $id)
                ->first();

            if ($result === null) {
                return null;
            }

            $model->setRawAttributes($result);
            return $model;
        } catch (Throwable $e) {
            throw new ModelException("Failed to find model: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get all models.
     * 
     * @return array<static>
     * @throws ModelException
     */
    public static function all(): array
    {
        try {
            $model = new static();
            $results = DB::table($model->getTable())->get();

            return array_map(function($result) use ($model) {
                $instance = new static();
                $instance->setRawAttributes($result);
                return $instance;
            }, $results);
        } catch (Throwable $e) {
            throw new ModelException("Failed to get all models: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Create and save a new model instance.
     * 
     * @param array<string, mixed> $attributes
     * @return static
     * @throws ModelException
     */
    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Query the model's table.
     * 
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @return QueryBuilder
     */
    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        $model = new static();
        return DB::table($model->getTable())->where($column, $operator, $value);
    }

    /**
     * Get the first model matching a query.
     * 
     * @return static|null
     * @throws ModelException
     */
    public static function first(): ?static
    {
        try {
            $model = new static();
            $result = DB::table($model->getTable())->first();

            if ($result === null) {
                return null;
            }

            $instance = new static();
            $instance->setRawAttributes($result);
            return $instance;
        } catch (Throwable $e) {
            throw new ModelException("Failed to get first model: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Set raw attributes from database query results.
     * 
     * @param array<string, mixed> $attributes
     * @return void
     */
    protected function setRawAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = true;
    }

    /**
     * Update a model.
     * 
     * @param array<string, mixed> $attributes
     * @return bool
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    /**
     * Get the fully qualified class name without the namespace.
     * 
     * @param string $class
     * @return string
     */
    protected static function class_basename(string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

/**
 * Helper function to get class basename.
 */
function class_basename(string|object $class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}
