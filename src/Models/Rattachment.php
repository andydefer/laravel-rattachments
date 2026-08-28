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
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Eloquent model representing an attachment between two polymorphic models.
 *
 * This model stores the relationship between a "rattachable" model (the source)
 * and a "target" model (the destination), along with a role and optional metadata.
 *
 * The role is stored as a string but resolved dynamically to an enum based on
 * the context of the attachment (rattachable_type + target_type).
 *
 * @property int $id
 * @property string $rattachable_type
 * @property int $rattachable_id
 * @property string $target_type
 * @property int $target_id
 * @property EnumerableInterface $role
 * @property StrictDataObject|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model $rattachable
 * @property-read Model $target
 */
final class Rattachment extends Model
{
    protected $table = 'rattachments';

    /** @var array<int, string> */
    protected $fillable = [
        'rattachable_type',
        'rattachable_id',
        'target_type',
        'target_id',
        'role',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
    ];

    private ?ConstraintValidatorInterface $constraintValidator = null;

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

    /**
     * Accessor and mutator for the role attribute.
     *
     * On read, resolves the stored role string to the appropriate enum instance
     * by inspecting the attachment context (rattachable_type + target_type).
     *
     * On write, validates that the role is allowed by the rattachable model's
     * constraints. This prevents invalid roles from being stored directly
     * through the model, bypassing the service layer.
     *
     * @return Attribute<string, EnumerableInterface>
     */
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
