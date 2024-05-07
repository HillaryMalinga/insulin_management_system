<?php

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public string $period = '-30 days';
    public string $lastActionTaken = 'Added new patient';

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
                $user->lastActionTaken = $this->lastActionTaken; 

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
    <x-card title="Administrators" separator shadow>
        <x-slot:menu>
            <x-button label="View all admins" icon-right="o-arrow-right" link="/users" class="btn-ghost btn-sm" />
        </x-slot:menu>

        @foreach($topCustomers as $customer)
           <x-list-item :item="$customer" sub-value="" link="/users/{{ $customer->id }}" no-separator>
                <x-slot:actions>
                {{-- <x-badge :value="$customer->amount" class="font-bold" /> --}}
                <span>Last action: {{ $this->lastActionTaken }}</span> 
                </x-slot:actions>
            </x-list-item>
        @endforeach
    </x-card>
</div>
