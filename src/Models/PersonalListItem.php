<?php

namespace KiranoDev\LaravelPersonalList\Models;

use KiranoDev\LaravelPersonalList\Contracts\Itemable;

class PersonalListItem
{
    public Itemable $original;
    public int $id;
    public int $price;
    public int $quantity;
    public bool $checked;
    public array $meta;
}