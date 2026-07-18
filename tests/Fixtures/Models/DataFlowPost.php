<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

final class DataFlowPost extends Model
{
    protected $table = 'posts';

    protected $guarded = [];
}
