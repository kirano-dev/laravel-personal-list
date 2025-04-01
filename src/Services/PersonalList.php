<?php

namespace KiranoDev\LaravelPersonalList\Services;

use Illuminate\Support\Facades\Cache;
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

    private function getDeviceIdentifier(): string {
        return hash('sha256', request()->header('User-Agent'));
    }

    private function getKey(): string {
        return $this->getDeviceIdentifier() . ".$this->name";
    }

    private function getData(): array {
        return Cache::get($this->getKey(), []);
    }

    public function remove(Itemable $item): self
    {
        unset($this->data[$item->id]);

        return $this;
    }

    public function has(Itemable $item): bool
    {
        return isset($this->data[$item->id]);
    }

    public function save(?array $data = null): void
    {
        Cache::set($this->getKey(), $data ?? $this->data);
    }

    public function add(
        Itemable $item,
        ?int $price = null,
        array $meta = [],
        int $quantity = 1
    ): self
    {
        if($this->has($item)) $this->remove($item);

        $this->data[$item->id] = new PersonalListItem(
            original: $item,
            id: $item->id,
            price: $price ?? $item->price ?? 0,
            meta: $meta,
            quantity: $quantity
        );

        return $this;
    }

    public function toggle(Itemable $item): self
    {
        if($this->has($item)) $this->remove($item);
        else $this->add($item);

        return $this;
    }

    public function toggleCheck(int $id): self
    {
        $this->data[$id]->checked = !$this->data[$id]->checked;

        return $this;
    }

    public function setQuantity(int $id, int $quantity): self
    {
        $this->data[$id]->quantity = $quantity;

        return $this;
    }

    public function total(): int
    {
        return array_reduce($this->data, fn($carry, $item) => $carry + ($item->price * $item->quantity), 0);
    }

    public function count(bool $deep = false): int {
        return $deep
            ? array_reduce($this->data, fn($carry, $item) => $carry + $item->quantity, 0)
            : count($this->data);
    }

    public function increment(PersonalListItem $item): self
    {
        $this->data[$item->id]->quantity = $this->data[$item->id]->quantity + 1;

        return $this;
    }

    public function decrement(PersonalListItem $item): self
    {
        $quantity = $this->data[$item->id]->quantity;

        if($quantity > 1) {
            $this->data[$item->id]->quantity = $quantity - 1;
        } else if ($quantity === 1) {
            $this->remove($item->original);
        }

        return $this;
    }

    public function clear(): void
    {
        $this->save([]);
    }
}
