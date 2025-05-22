<?php

namespace KiranoDev\LaravelPersonalList\Services;

use Illuminate\Support\Collection;
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

    public function add(Itemable $item, ?int $price = null, ?array $meta = null, int $quantity = 1): self
    {
        if($this->has($item)) $this->remove($item);

        $personalListItem = new PersonalListItem();

        $personalListItem->original = $item;
        $personalListItem->price = $price ?? $item->price;
        $personalListItem->quantity = $quantity;
        $personalListItem->checked = true;
        $personalListItem->id = $item->id;

        $personalListItem->meta = $meta ?? $item->getMeta();

        $this->data[$item->id] = $personalListItem;

        return $this;
    }

    public function toggle(Itemable $item, ...$args): void
    {
        if($this->has($item)) $this->remove($item);
        else $this->add($item, ...$args);

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

    public function checked(): array
    {
        return array_filter($this->data, fn ($item) => $item->checked);
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

    public function increment(PersonalListItem $item): void
    {
        $this->data[$item->id]->quantity = $this->data[$item->id]->quantity + 1;

        $this->save();
    }

    public function decrement(PersonalListItem $item): void
    {
        $quantity = $this->data[$item->id]->quantity;

        if($quantity > 1) {
            $this->data[$item->id]->quantity = $quantity - 1;
        } else if ($quantity === 1) {
            $this->remove($item->original);
        }

        $this->save();
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function getCollection(): Collection
    {
        return collect(
            array_map(fn (PersonalListItem $item) => $item->original, $this->data)
        );
    }

    public function clearChecked(): void
    {
        $checked_ids = array_keys($this->checked());

        $this->save(
            array_filter(
                $this->data,
                fn (PersonalListItem $item) => !in_array($item->id, $checked_ids)
            )
        );
    }
}
