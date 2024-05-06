<?php

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public string $period = '-30 days';
    public string $lastSugarLevel = '2.8 mmol/L';

    public function topCustomers(): Collection
    {
        return Order::query()
            ->with('user.country')
            ->selectRaw("sum(total) as amount, user_id")
            ->where('created_at', '>=', Carbon::parse($this->period)->startOfDay())
            ->groupBy('user_id')
            ->orderByDesc('amount')
            ->take(3)
            ->get() // Execute the query here
            ->transform(function (Order $order) {
                $user = $order->user;
                $user->amount = Number::currency($order->amount);
                $user->lastSugarLevel = $this->lastSugarLevel; 

                return $user;
            });
    }

    public function with(): array
    {
        return [
            'topCustomers' => $this->topCustomers(),
        ];
    }

 

}; ?>

<div>
    <x-card title="Critical levels patients" separator shadow>
        <x-slot:menu>
            <x-button label="View all patients" icon-right="o-arrow-right" link="/users" class="btn-ghost btn-sm" />
        </x-slot:menu>

        @foreach($topCustomers as $customer)
           <x-list-item :item="$customer" sub-value="" link="/users/{{ $customer->id }}" no-separator>
                <x-slot:actions>
                {{-- <x-badge :value="$customer->amount" class="font-bold" /> --}}
                <span>Last recorded: {{ $this->lastSugarLevel }}</span> 
                </x-slot:actions>
            </x-list-item>
        @endforeach
    </x-card>
</div>
