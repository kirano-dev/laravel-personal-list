<?php

namespace KiranoDev\LaravelPersonalList\Services;

use KiranoDev\LaravelPersonalList\Contracts\Itemable;
use KiranoDev\LaravelPersonalList\Models\PersonalListItem;

class PersonalList
{
    public array $data;

    public function __construct(
        public string $name = 'cart'
    ) {
        $this->data = $this->getData();
    }

    private function getKey(): string {
        return request()->ip() . ".$this->name";
    }

    private function getData(): array {
        return cache()->get($this->getKey(), []);
    }

    public function remove(Itemable $item): void
    {
        unset($this->data[$item->id]);
    }

    public function has(Itemable $item): bool
    {
        return isset($this->data[$item->id]);
    }

    public function save(?array $data = null): void
    {
        cache()->set($this->getKey(), $data ?? $this->data);
    }

    public function add(Itemable $item): self
    {
        if($this->has($item)) $this->remove($item);

        $personalListItem = new PersonalListItem();

        $personalListItem->original = $item;
        $personalListItem->price = $item->price;
        $personalListItem->quantity = 1;
        $personalListItem->checked = true;
        $personalListItem->id = $item->id;

        $personalListItem->meta = $item->getMeta();

        $this->data[$item->id] = $personalListItem;

        return $this;
    }

    public function toggle(Itemable $item): void
    {
        if($this->has($item)) $this->remove($item);
        else $this->add($item);

        $this->save();
    }

    public function toggleCheck(int $id): void
    {
        $this->data[$id]->checked = !$this->data[$id]->checked;

        $this->save();
    }

    public function setQuantity(int $id, int $quantity): void
    {
        $this->data[$id]->quantity = $quantity;

        $this->save();
    }

    public function total(): int
    {
        return array_reduce($this->data, fn($carry, $item) => $carry + ($item->price * $item->quantity), 0);
    }

    public function count(): int {
        return count($this->data);
    }

    public function increment(PersonalListItem $item): void
    {
        $this->data[$item->id]->quantity = $this->data[$item->id]->quantity + 1;

        $this->save();
    }

    public function decrement(PersonalListItem $item): void
    {
        $quantity = $this->data[$item->id]->quantity;

        if($quantity > 1) {
            $this->data[$item->id]->quantity =  - 1;
        } else if ($quantity === 1) {
            $this->remove($item->original);
        }

        $this->save();
    }

    public function clear(): void
    {
        $this->save([]);
    }
}
