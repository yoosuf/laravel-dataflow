<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DataFlowUser extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'profile' => 'array',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DataFlowPost::class, 'user_id');
    }

    public function scopeTenant(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }
}
