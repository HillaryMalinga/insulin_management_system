<?php

use App\Actions\DeleteCategoryAction;
use App\Exceptions\AppException;
use App\Models\Category;
use App\Traits\ResetsPaginationWhenPropsChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;

new class extends Component {

  

    use Toast, WithPagination, ResetsPaginationWhenPropsChanges;

    #[Url]
    public string $search = '';

    public string $period = '-30 days';

    #[Url]
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Selected Category to edit on modal
    public ?Category $category;

    #[On('category-saved')]
    #[On('category-cancel')]
    public function clear(): void
    {
        $this->reset();
    }

    public function edit(Category $category): void
    {
        $this->category = $category;
    }

    public function delete(Category $category): void
    {
        $delete = new DeleteCategoryAction($category);
        $delete->execute();

        $this->success('Category deleted.');
    }

    public function categories(): LengthAwarePaginator
    {
        return Category::query()
            ->withCount('products')
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(9);
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-20'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'products_count', 'label' => 'Products', 'class' => 'w-32', 'sortBy' => 'products_count'],
            ['key' => 'date_human', 'label' => 'Created at', 'class' => 'hidden lg:table-cell']
        ];
    }

    public function with(): array
    {
        return [
            'categories' => $this->categories(),
            'headers' => $this->headers()
        ];
    }
}; ?>

<div>
    {{--  HEADER  --}}
    <x-header title="Analytics" separator progress-indicator>
        {{-- <x-select :options="$periods" wire:model.live="period" icon="o-calendar"/> --}}
    </x-header>

{{--    --}}{{-- TABLE --}}
{{--    <x-card>--}}
{{--        <x-table :headers="$headers" :rows="$categories" @row-click="$wire.edit($event.detail.id)" :sort-by="$sortBy" with-pagination>--}}
{{--            @scope('actions', $category)--}}
{{--            <x-button wire:click="delete({{ $category->id }})" icon="o-trash" class="btn-sm btn-ghost text-error" wire:confirm="Are you sure?" spinner />--}}
{{--            @endscope--}}
{{--        </x-table>--}}
{{--    </x-card>--}}

{{--    --}}{{-- EDIT MODAL --}}
{{--    <livewire:categories.edit wire:model="category" />--}}

    @if(auth()->user()->role === 'admin')
    {{-- PATIENTS --}}
    <div class="flex flex-col gap-8 mt-8">
        <div>
            <x-card title="Patients" separator shadow>
                <div class="grid grid-cols-3 gap-4 content-start">
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Patients</p> 
                            <p class="text-xl font-black">59</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Adults</p> 
                            <p class="text-xl font-black">43</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Adolescents</p> 
                            <p class="text-xl font-black">16</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Hypoglycemic</p> 
                            <p class="text-xl font-black">10</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Hyperglycemic</p> 
                            <p class="text-xl font-black">0</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Critical Levels</p> 
                            <p class="text-xl font-black">0</p>
                        </div>    
                    </div>
                </div>
            </x-card>
        </div>

        {{-- ADMINISTRATORS --}}
        <div>
           <x-card title="Administrators" separator shadow>
                <div class="grid grid-cols-3 gap-4 content-start">
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Administrators</p> 
                            <p class="text-xl font-black">3</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Clinical</p> 
                            <p class="text-xl font-black">2</p>
                        </div>    
                    </div>
                    <div class="px-5 py-4  cursor-pointer hover:bg-gray-100 hover:bg-opacity-5 rounded duration-200" >
                        <div >
                            <p class="text-xs text-gray-500">Total Clerical</p> 
                            <p class="text-xl font-black">1</p>
                        </div>    
                    </div>
                </div>
            </x-card>
        </div>
    </div>
    @else
    <div>
        <x-card title="Activities" separator shadow>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4">
                    <div >
                        <p class="text-xs text-gray-500">Total Eating</p> 
                        <p class="text-xl font-black">26</p>
                        <div class=" col-span-6 lg:col-span-3" >
                            <livewire:categories.chart-eating :$period/>
                        </div> 
                    </div>    
                </div>
                <div class="p-4">
                    <div >
                        <p class="text-xs text-gray-500">Total Exercising</p> 
                        <p class="text-xl font-black">15</p>
                        <div class=" col-span-6 lg:col-span-3" >
                            <livewire:categories.chart-eating :$period/>
                        </div> 
                    </div>
                </div>
            </div>
        </x-card>
    </div>
    @endif
</div>
