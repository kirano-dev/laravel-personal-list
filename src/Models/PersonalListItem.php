<?php

namespace KiranoDev\LaravelPersonalList\Models;

use KiranoDev\LaravelPersonalList\Contracts\Itemable;

class PersonalListItem
{
    public function __construct(
        public Itemable $original,
        public int $id,
        public int $price,
        public array $meta,
        public int $quantity = 1,
        public bool $checked = true,
    ) {}
}