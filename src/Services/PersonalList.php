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
        return request()->ip() . $this->name;
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
        $personalListItem->quantity = 1;
        $personalListItem->checked = true;

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

    public function setCount(int $id, int $count): void
    {
        $this->data[$id]->count = $count;

        $this->save();
    }

    public function total(): int {
        return count($this->data);
    }

    public function clear(): void
    {
        $this->save([]);
    }
}
