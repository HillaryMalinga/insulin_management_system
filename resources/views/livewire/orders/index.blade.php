<?php

use App\Models\Country;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use App\Traits\ClearsProperties;
use App\Traits\ResetsPaginationWhenPropsChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, ResetsPaginationWhenPropsChanges, ClearsProperties;

    #[Url]
    public int $status_id = 0;

    #[Url]
    public ?int $country_id = 0;

    #[Url]
    public string $name = '';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public bool $showFilters = false;

    public User $user;

    public function mount(): void
    {
        $this->user = auth()->user()->load('activity');
    }

    // Count filter types
    public function filterCount(): int
    {
        return ($this->status_id ? 1 : 0) + ($this->country_id ? 1 : 0) + (strlen($this->name) ? 1 : 0);
    }

    public function activities(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->user->activity()->paginate(10);
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'hidden lg:table-cell'],
            ['key' => 'date_human', 'label' => 'Date', 'sortBy' => 'created_at', 'class' => 'hidden lg:table-cell'],
            ['key' =>'sugar_level' , 'label' => 'Sugar Level', 'sortBy' => 'sugar_level','class'=>'hidden lg:table-cell'],
            ['key' => 'protocol', 'label' => 'Activity', 'sortBy' => 'protocol'],
            ['key' => 'activity_description', 'label' => 'Description', 'sortBy' => 'activity_description', 'class' => 'hidden lg:table-cell'],
            // ['key' => 'status', 'label' => 'Status', 'sortBy' => 'status', 'class' => 'hidden lg:table-cell']
        ];
    }

    public function with(): array
    {
        return [
            'orders' => $this->activities(),
            'headers' => $this->headers(),
            'filterCount' => $this->filterCount()
        ];
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Entries" separator progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search entry..." wire:model.live.debounce="name" icon="o-magnifying-glass" clearable/>
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            {{-- <x-button label="Filters"
                      icon="o-funnel"
                      :badge="$filterCount"
                      badge-classes="font-mono"
                      @click="$wire.showFilters = true"
                      class="bg-base-300"
                      responsive/> --}}

            <x-button label="New Entry" link="/orders/create" icon="o-plus" class="btn-primary" responsive/>
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card shadow>
        <x-table :headers="$headers" :rows="$orders" link="/orders/{id}/edit" with-pagination :sort-by="$sortBy">
{{--            @scope('cell_status', $order)--}}
{{--               <x-badge :value="$order->status->name" :class="$order->status->color"/>--}}
{{--            @endscope--}}
        </x-table>
    </x-card>

</div>
