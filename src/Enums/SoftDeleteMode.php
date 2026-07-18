<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Enums;

enum SoftDeleteMode: string
{
    case WithoutTrashed = 'without-trashed';
    case WithTrashed = 'with-trashed';
    case OnlyTrashed = 'only-trashed';
}
