<?php

namespace Core\Validation;

use Closure;

/**
 * Small, dependency-free input validator.
 */
class Validator
{
    /** @var array<string, string|Closure> */
    private array $rules;

    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, array<int, string>> */
    private array $validationErrors = [];

    /** @var array<string, mixed> */
    private array $validatedData = [];

    public function __construct(array $data, array $rules, private readonly array $files = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules, array $files = []): self
    {
        return new self($data, $rules, $files);
    }

    public function fails(): bool
    {
        return $this->validationErrors !== [];
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->validatedData;
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->validationErrors;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            $fieldRules = is_string($rules) ? explode('|', $rules) : [$rules];
            $valid = true;

            foreach ($fieldRules as $rule) {
                if ($rule instanceof Closure) {
                    $result = $rule($value, $this->data);
                    if ($result !== true && $result !== null) {
                        $this->addError($field, is_string($result) ? $result : 'The value is invalid.');
                        $valid = false;
                    }
                    continue;
                }

                [$name, $arguments] = $this->parseRule($rule);
                $message = $this->checkRule($name, $arguments, $field, $value);
                if ($message !== null) {
                    $this->addError($field, $message);
                    $valid = false;
                }
            }

            if ($valid && array_key_exists($field, $this->data)) {
                $this->validatedData[$field] = $value;
            }
        }
    }

    private function checkRule(string $rule, array $arguments, string $field, mixed $value): ?string
    {
        $present = $value !== null && $value !== '';
        return match ($rule) {
            'required' => $present ? null : "The {$field} field is required.",
            'email' => !$present || filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "The {$field} must be a valid email address.",
            'min' => !$present || mb_strlen((string) $value) >= (int) ($arguments[0] ?? 0) ? null : "The {$field} must be at least {$arguments[0]} characters.",
            'max' => !$present || mb_strlen((string) $value) <= (int) ($arguments[0] ?? PHP_INT_MAX) ? null : "The {$field} may not exceed {$arguments[0]} characters.",
            'regex' => !$present || (@preg_match($arguments[0] ?? '//', (string) $value) === 1) ? null : "The {$field} format is invalid.",
            'integer' => !$present || filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "The {$field} must be an integer.",
            'boolean' => !$present || filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null ? null : "The {$field} must be boolean.",
            'file' => $this->checkFile($field, $value) === null ? null : $this->checkFile($field, $value),
            'mime', 'mimes' => $this->checkMime($field, $arguments),
            'size', 'max_size' => $this->checkSize($field, $arguments),
            default => "Unknown validation rule: {$rule}.",
        };
    }

    private function checkFile(string $field, mixed $value): ?string
    {
        $file = $this->files[$field] ?? $value;
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            ? null
            : "The {$field} must be a valid uploaded file.";
    }

    private function checkMime(string $field, array $arguments): ?string
    {
        $file = $this->files[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return "The {$field} must be a valid uploaded file.";
        }
        $allowed = array_map('strtolower', $arguments);
        $mime = strtolower((string) ($file['type'] ?? ''));
        return in_array($mime, $allowed, true) || in_array(strtolower((string) pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)), $allowed, true)
            ? null
            : "The {$field} has an invalid file type.";
    }

    private function checkSize(string $field, array $arguments): ?string
    {
        $file = $this->files[$field] ?? null;
        $maxBytes = (int) ($arguments[0] ?? 0);
        return is_array($file) && (int) ($file['size'] ?? 0) <= $maxBytes
            ? null
            : "The {$field} may not exceed {$maxBytes} bytes.";
    }

    /** @return array{string, array<int, string>} */
    private function parseRule(string|Closure $rule): array
    {
        if ($rule instanceof Closure) {
            return ['', []];
        }
        [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');
        return [$name, $parameters === '' ? [] : str_getcsv($parameters)];
    }

    private function addError(string $field, string $message): void
    {
        $this->validationErrors[$field][] = $message;
    }
}