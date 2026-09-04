<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Models;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\LaravelRattachments\Contracts\Validation\ConstraintValidatorInterface;
use AndyDefer\Repository\Contracts\EnumerableInterface;
use AndyDefer\Repository\Proxies\AttributeProxy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class Rattachment extends Model
{
    protected $table = 'rattachments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'rattachable_type',
        'rattachable_id',
        'target_type',
        'target_id',
        'role',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    private ?ConstraintValidatorInterface $constraintValidator = null;

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    private function getConstraintValidator(): ConstraintValidatorInterface
    {
        if ($this->constraintValidator === null) {
            $this->constraintValidator = app(ConstraintValidatorInterface::class);
        }

        return $this->constraintValidator;
    }

    public function rattachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes): EnumerableInterface {
                if ($value === null) {
                    throw new \RuntimeException('Role cannot be null.');
                }

                $rattachableClass = $attributes['rattachable_type'];
                $targetClass = $attributes['target_type'];
                $roleValue = $value;

                return $this->getConstraintValidator()->resolveRole(
                    $rattachableClass,
                    $targetClass,
                    $roleValue
                );
            },
            set: function ($value, array $attributes): string {
                if ($value === null) {
                    throw new InvalidArgumentException('Role cannot be null.');
                }

                if ($value instanceof EnumerableInterface) {
                    $rawValue = $value->getValue();
                } elseif (is_string($value) || is_int($value)) {
                    $rawValue = (string) $value;
                } else {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Role must be an instance of EnumerableInterface, a string, or an int. Got: %s',
                            get_debug_type($value)
                        )
                    );
                }

                $rattachableClass = $attributes['rattachable_type'] ?? $this->rattachable_type ?? null;
                $targetClass = $attributes['target_type'] ?? $this->target_type ?? null;

                if ($rattachableClass === null || $targetClass === null) {
                    throw new \RuntimeException(
                        'Cannot validate role without rattachable_type and target_type.'
                    );
                }

                $this->getConstraintValidator()->validateRoleValue(
                    $rattachableClass,
                    $targetClass,
                    $rawValue
                );

                return $rawValue;
            }
        );
    }

    protected function metadata(): Attribute
    {
        return AttributeProxy::nullable(
            StrictDataObject::class,
            column: 'metadata'
        );
    }
}
