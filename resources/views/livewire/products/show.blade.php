<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(): void
    {
        $this->user->load(['patientInformation','patientAthrometric','patientPhysiology']);
    }

    public function favorites(): Collection
    {
        return OrderItem::query()
            ->with('product.category')
            ->selectRaw("count(1) as amount, product_id")
            ->whereRelation('order', 'user_id', $this->user->id)
            ->groupBy('product_id')
            ->orderByDesc('amount')
            ->take(2)
            ->get()
            ->transform(function (OrderItem $item) {
                $product = $item->product;
                $product->amount = $item->amount;

                return $product;
            });
    }

    public function orders(): Collection
    {
        return Order::with(['user', 'status'])
            ->where('user_id', $this->user->id)
            ->latest('id')
            ->take(5)
            ->get();
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'date_human', 'label' => 'Date'],
            ['key' => 'total_human', 'label' => 'Total'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'hidden lg:table-cell']
        ];
    }

    public function with(): array
    {
        return [
            'favorites' => $this->favorites(),
            'orders' => $this->orders(),
            'headers' => $this->headers()
        ];
    }
}; ?>

<div>
    <x-header :title="$user->name" separator>
        <x-slot:actions>
            <x-button label="View patient enties" link="/orders" icon="o-eye" class="btn-primary" responsive/>
            <x-button label="Edit patient" link="/users/{{ $user->id }}/edit" icon="o-pencil" class="btn-primary" responsive/>
            <x-button label="Delete patient" icon="o-trash" wire:click="delete" class="btn-error text-gray-100" wire:confirm="Are you sure?" spinner responsive />
            <x-button label="Back" link="/users" icon="o-arrow-uturn-left" responsive/>    
        </x-slot:actions>
    </x-header>

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- PERSONAL INFO --}}
        <x-card title="Personal Info" separator shadow>
            <div class="flex gap-4 text-sm">
                <x-avatar :image="$user->avatar" class="!w-20">
                {{-- <x-slot:title class="pl-2">
                {{ $user->name }}
                </x-slot:title> --}}
                {{-- <x-slot:subtitle class="flex flex-col gap-2 p-2 pl-2">
                    <x-icon name="o-envelope" :label="$user->email"/>
                    <x-icon name="o-folder-minus"
                            :label="Carbon::parse($user?->patientInformation->first()?->dob)->age . ' years'"/>
                    <x-icon name="o-user-plus"
                            :label="$user?->patientInformation->first()?->class"/>
                </x-slot:subtitle> --}}
            </x-avatar>
            <div class="flex flex-col gap-2" >
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Phone:</p>
                    <p>0772 222 333 <span class="font-bold text-red-500">*</span> </p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Email:</p>
                    <p>{{$user->email}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Physical adress:</p>
                    <p>{{ $user->patientInformation->first()->address }}</p>
                </div>  
            </div>
        </div>
            
        </x-card>

        {{-- DEMOGRAPHICS --}}
        <x-card title="Demographics" separator shadow>
            <div class="flex flex-col gap-2">
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Gender:</p>
                    <p>{{$user?->patientInformation->first()->gender}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">D.O.B:</p>
                    <p>
                        {{ Carbon::parse($user?->patientInformation->first()?->dob)->format('d-m-y') }}  ({{ Carbon::parse($user?->patientInformation->first()?->dob)->age . ' years' }})
                    </p>
                </div>
            </div>
        </x-card>  
    </div>


    <div class="grid lg:grid-cols-2 gap-8 mt-8">
        {{-- ANTHROPOMETRY --}}
        <x-card title="Anthropometry" separator shadow>
            <div class="flex flex-col gap-2">
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Height:</p>
                    <p>{{$user->patientAthrometric->first()->height.' cm'}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Weight:</p>
                    <p>{{$user->patientAthrometric->first()->weight.' kg'}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">BMI:</p>
                    <p>{{$user?->patientAthrometric->first()->bmi}} <span class="font-bold text-red-500">*</span></p>
                </div>  
            </div>
        </x-card>

        {{-- PHYSIOLOGY --}}
        <x-card title="Physiology" separator shadow>
            {{-- <div class="grid grid-cols-2 gap-4 content-start">
                <div>
                    <x-list-item :item="$user?->patientInformation->first()" sub-value="category.name" avatar="cover"
                                 no-separator>
                        <x-slot:value>
                            {{ __('BMI : ').$user?->patientAthrometric->first()->bmi}}
                        </x-slot:value>
                    </x-list-item>
                    <x-list-item :item="$user?->patientPhysiology->first()" sub-value="category.name" avatar="cover"
                                 no-separator>
                        <x-slot:value>
                            {{ __('TBV : ').$user?->patientPhysiology->first()->tbv}}
                        </x-slot:value>
                    </x-list-item>
                    <x-list-item :item="$user?->patientPhysiology->first()" sub-value="category.name" avatar="cover"
                                 no-separator>
                        <x-slot:value>
                            {{ __('CBGR : ').$user?->patientPhysiology->first()->cbgr}}
                        </x-slot:value>
                    </x-list-item>
                </div>
                <div>
                    <x-list-item :item="$user?->patientPhysiology->first()" sub-value="category.name" avatar="cover"
                                 no-separator>
                        <x-slot:value>
                            {{ __('ISF : ').$user?->patientPhysiology->first()->isf}}
                        </x-slot:value>
                    </x-list-item>
                    <x-list-item :item="$user?->patientPhysiology->first()" sub-value="category.name" avatar="cover"
                                 no-separator>
                        <x-slot:value>
                            {{ __('DIA : ').$user?->patientPhysiology->first()->dia}}
                        </x-slot:value>
                    </x-list-item>
                </div>
            </div> --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">TBV:</p>
                    <p>{{$user?->patientPhysiology->first()->tbv. ' mL/kg'}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">CBGR:</p>
                    <p>{{$user?->patientPhysiology->first()->cbgr. '/min'}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">TDD:</p>
                    <p>50 <span class="font-bold text-red-500">*</span> </p>
                </div>  
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">ICR:</p>
                    <p>10 <span class="font-bold text-red-500">*</span> </p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">ISF:</p>
                    <p>{{$user?->patientPhysiology->first()->isf}}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">DIA:</p>
                    <p>{{$user?->patientPhysiology->first()->dia.' hrs'}}</p>
                </div>  
            </div>
        </x-card> 
    </div>

    <div class="mt-8" >
        {{-- EXERCISE INFO --}}
        <x-card title="Exercise Info" separator shadow >
            <div class="grid grid-cols-2 gap-4" >
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Heart Rate at Rest:</p>
                    <p>72 bpm <span class="font-bold text-red-500">*</span> </p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">Max heart rate:</p>
                    <p>176 bpm <span class="font-bold text-red-500">*</span> </p>
                </div>
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">VO2 Max:</p>
                    <p>36.7 mL/kg/min <span class="font-bold text-red-500">*</span> </p>
                </div>  
                <div class="flex gap-2 text-sm">
                    <p class="font-bold">METs Max:</p>
                    <p>10.5 <span class="font-bold text-red-500">*</span> </p>
                </div>  
            </div>  
        </x-card>
    </div>

    {{-- RECENT ORDERS --}}
    {{-- <x-card title="Recent Data" separator shadow class="mt-8">
        <x-table :rows="$orders" :headers="$headers" link="/orders/{id}/edit">
            @scope('cell_status', $order)
            <x-badge :value="$order->status->name" :class="$order->status->color"/>
            @endscope
        </x-table>

        @if(!$orders->count())
            <x-icon name="o-list-bullet" label="Nothing here." class="text-gray-400 mt-5"/>
        @endif
    </x-card> --}} 
</div>
