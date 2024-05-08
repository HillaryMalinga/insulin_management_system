<?php

use App\Actions\Order\DeleteOrderAction;
use App\Actions\Order\DeleteOrderItemAction;
use App\Actions\Order\UpdateOrderItemQuantityAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public Order $order;

    #[Rule(['items.*.quantity' => 'required|integer'])]
    public array $items = [];

    public function mount(): void
    {
        $this->order->load(['status', 'items.product.brand', 'items.product.category']);
        $this->items = $this->order->items->toArray();
    }

    #[On('item-added')]
    public function refreshItems(): void
    {
        $this->items = $this->order->items->toArray();
    }

    public function updateQuantity(OrderItem $item, int $quantity): void
    {
        $update = new UpdateOrderItemQuantityAction($item, $quantity);
        $update->execute();

        $this->order->refresh();
    }

    // Delete the order
    public function delete(): void
    {
        $delete = new DeleteOrderAction($this->order);
        $delete->execute();

        $this->success('Order deleted with success.', redirectTo: '/orders');
    }

    // Remove an item for order
    public function deleteItem(OrderItem $item): void
    {
        $remove = new DeleteOrderItemAction($item);
        $remove->execute();
        $this->success('Item removed.');

        $this->order->refresh();
    }

    public function headers(): array
    {
        return [
            ['key' => 'price_human', 'label' => 'Time', 'class' => 'hidden lg:table-cell'],
            ['key' => 'product.name', 'label' => 'Action taken', 'class' => 'hidden lg:table-cell'],
            ['key' => 'product.brand.name', 'label' => 'Action results', 'class' => 'hidden lg:table-cell'],
        ];
    }

    // Quantities to display on x-select
    public function quantities(): Collection
    {
        $items = collect();

        collect(range(1, 9))->each(fn($item) => $items->add(['id' => $item, 'name' => $item]));

        return $items;
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'quantities' => $this->quantities()
        ];
    }
}; ?>

<div>
    <x-header title="View entry" separator>
        {{-- <x-slot:actions>
            <x-button label="Delete" icon="o-trash" wire:click="delete" class="btn-error" wire:confirm="Are you sure?" spinner responsive />
        </x-slot:actions> --}}
         <x-slot:actions>
            <x-button label="Back" link="/orders" icon="o-arrow-uturn-left" responsive/>
        </x-slot:actions>
    </x-header>

    <div class="">
        {{-- CUSTOMER --}}
        {{-- <livewire:orders.customer :$order /> --}}

        {{-- ENTRY INFO --}}
        <x-card title="Entry info" separator shadow>
            <div class="grid grid-cols-3 gap-4">
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Activity:</p>
                    <p>Eating <span class="font-bold text-red-500">*</span></p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Entry date:</p>
                    <p>07-05-24 <span class="font-bold text-red-500">*</span></p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Start time:</p>
                    <p>13:25 <span class="font-bold text-red-500">*</span></p>
                </div>  
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Finish time:</p>
                    <p>13:25 <span class="font-bold text-red-500">*</span></p>
                </div>  
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Starting sugar level:</p>
                    <p>5.2 mmol/L <span class="font-bold text-red-500">*</span></p>
                </div>  
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">No. of actions:</p>
                    <p>1 <span class="font-bold text-red-500">*</span></p>
                </div>  
            </div>
        </x-card>
    </div>

    {{-- ACTIONS LOG --}}
    <x-card title="Entry actions log" separator progress-indicator="updateQuantity" shadow class="mt-8">
        {{-- <x-slot:menu>
            <livewire:orders.add-item :order="$order" />
        </x-slot:menu> --}}

        <x-table :rows="$order->items" :headers="$headers">
            {{-- Cover image scope --}}
            @scope('cell_product.cover', $item)
            <x-avatar :image="$item->product->cover" class="!w-10 !rounded-lg" />
            @endscope

            {{-- Quantity scope --}}
            @scope('cell_quantity', $item, $quantities)
            <x-select wire:model.number="items.{{ $this->loop->index }}.quantity" :options="$quantities" wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                      class="select-sm !w-14" />
            @endscope

            {{-- Actions scope
            @scope('actions', $item)
            <x-button icon="o-trash" wire:click="deleteItem({{ $item->id }})" spinner class="btn-ghost text-error btn-sm" />
            @endscope --}}
        </x-table>

        @if(!$order->items->count())
            <x-icon name="o-list-bullet" label="Nothing here." class="text-gray-400 mt-5" />
        @endif
    </x-card>
</div>
