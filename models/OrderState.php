<?php

declare(strict_types=1);

namespace WebBook\Mall\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Lang;
use Model;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use ValidationException;
use WebBook\Mall\Classes\Database\IsStates;

class OrderState extends Model
{
    use IsStates;
    use SoftDelete;
    use Sortable;
    use Validation;

    /**
     * Disable `is_default` handler on IsStates trait.
     * @var null|string
     */
    public const IS_DEFAULT = null;

    /**
     * Enable `is_enabled` handler on IsStates trait, by passing the column name.
     * @var null|string
     */
    public const IS_ENABLED = 'is_enabled';

    /**
     * Default NEW order state flag
     * @var string
     */
    public const FLAG_NEW = 'NEW';

    /**
     * Default CANCELLED order state flag
     * @var string
     */
    public const FLAG_CANCELLED = 'CANCELLED';

    /**
     * Default COMPLETE order state flag
     * @var string
     */
    public const FLAG_COMPLETE = 'COMPLETE';

    /**
     * The available order state flag options.
     * @var array
     */
    public static $availableFlagOptions = [
        self::FLAG_CANCELLED => 'webbook.mall::lang.order_states.flags.cancelled',
        self::FLAG_COMPLETE  => 'webbook.mall::lang.order_states.flags.complete',
        self::FLAG_NEW       => 'webbook.mall::lang.order_states.flags.new',
    ];

    /**
     * Implement behaviors for this model.
     * @var array
     */
    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /**
     * The table associated with this model.
     * @var string
     */
    public $table = 'webbook_mall_order_states';

    /**
     * The translatable attributes of this model.
     * @var array
     */
    public $translatable = [
        'name',
        'description',
    ];

    /**
     * The validation rules for the single attributes.
     * @var array
     */
    public $rules = [
        'name'          => 'required',
        'is_enabled'    => 'nullable|boolean',
        'is_internal'      => 'nullable|boolean',
        'display_state_id' => 'nullable|required_if:is_internal,1|integer|exists:webbook_mall_order_states,id',
    ];

    /**
     * The attributes that are mass assignable.
     * @var array<string>
     */
    public $fillable = [
        'name',
        'description',
        'flag',
        'is_enabled',
        'is_internal',
        'display_state_id',
    ];

    /**
     * The attributes that should be cast.
     * @var array
     */
    public $casts = [
        'is_enabled'    => 'boolean',
        'is_internal' => 'boolean',
        'deleted_at'    => 'datetime',
    ];

    /**
     * The belongsTo relationships of this model.
     * @var array
     */
    public $belongsTo = [
        'display_state' => [self::class, 'key' => 'display_state_id'],
    ];

    /**
     * The hasMany relationships of this model.
     * @var array
     */
    public $hasMany = [
        'orders' => Order::class,
    ];

    /**
     * Return the available translated orderState flag options.
     * @return array
     */
    public function getFlagOptions(): array
    {
        return array_map(fn (string $val) => Lang::get($val), static::$availableFlagOptions);
    }

    public function getDisplayStateOptions(): array
    {
        return self::query()
            ->public()
            ->when(static::IS_ENABLED, fn($q) => $q->where('is_enabled', true))
            ->when($this->id, fn($q) => $q->where('id', '!=', $this->id))
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Include all payment methods, even disabled ones.
     * @param Builder $query
     * @return void
     */
    public function scopeAll(Builder $query)
    {
        return $query->withDisabled();
    }

    public function scopePublic(Builder $q)
    {
        return $q->where('is_internal', false);
    }

    public function scopeInternal(Builder $q)
    {
        return $q->where('is_internal', true);
    }

    public function beforeSave()
    {
        if (!$this->is_internal) {
            $this->display_state_id = null;
            return;
        }

        // Internal: must have a target
        if (!$this->display_state_id) {
            throw ValidationException::withMessages([
                'display_state_id' => Lang::get('Choose a public status to display.'),
            ]);
        }

        // Cannot map to itself
        if ($this->exists && (int)$this->id === (int)$this->display_state_id) {
            throw ValidationException::withMessages([
                'display_state_id' => Lang::get('Internal state cannot display as itself.'),
            ]);
        }

        // Target must be non-internal and enabled
        $target = self::query()->find($this->display_state_id);
        if (!$target) {
            throw ValidationException::withMessages(['display_state_id' => Lang::get('Selected status not found.')]);
        }
        if ($target->is_internal) {
            throw ValidationException::withMessages([
                'display_state_id' => Lang::get('Display status must be a non-internal status.'),
            ]);
        }
        if (static::IS_ENABLED && !$target->is_enabled) {
            throw ValidationException::withMessages([
                'display_state_id' => Lang::get('Display status must be enabled.'),
            ]);
        }
    }

    /**
     * Undocumented function
     * @return void
     */
    public function beforeDelete()
    {
        if (!empty($this->flag)) {
            throw new Exception('You cannot delete a flagged order state.');
        }
    }

    public function getPublicNameAttribute(): string
    {
        return $this->getPublicDisplayState()->name ?? $this->name;
    }

    public function getPublicFlagAttribute(): ?string
    {
        return $this->getPublicDisplayState()->flag ?? $this->flag;
    }

    public function getPublicDisplayState(): self
    {
        if ($this->is_internal && $this->display_state) {
            return $this->display_state;
        }
        return $this;
    }

    public function isInternal(): bool
    {
        return (bool) $this->is_internal;
    }
}
