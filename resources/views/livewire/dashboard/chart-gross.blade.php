<?php

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public string $period = '-30 days';

//    public bool $admin;
//
//    public function mount(): void
//    {
//        $this->admin = auth()->user()->is_admin;
//    }

    public array $chartGross = [
        'type' => 'line',
        'options' => [
            'backgroundColor' => '#dfd7f7',
            'resposive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'display' => false
                ],
                'y' => [
                    'display' => false
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ]
            ],
        ],
        'data' => [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data' => [],
                    'tension' => '0.1',
                    'fill' => true,
                ],
            ]
        ]
    ];

    #[Computed]
    public function refreshChartGross(): void
    {
        $sales = Order::query()
            ->selectRaw("DATE_FORMAT('%Y-%m-%d', created_at) as day, sum(total) as total")
            ->groupBy('day')
            ->where('created_at', '>=', Carbon::parse($this->period)->startOfDay())
            ->get();

        Arr::set($this->chartGross, 'data.labels', $sales->pluck('day'));
        Arr::set($this->chartGross, 'data.datasets.0.data', $sales->pluck('total'));
    }

    public function with(): array
    {
        $this->refreshChartGross();

        return [];
    }
}; ?>

<div>
    <x-card title="{{$admin = auth()->user()->is_admin ? __('Averages') :  __('Blood Glucose Trend')}}" separator
            shadow>
        <x-chart wire:model="chartGross" class="h-44"/>
    </x-card>
</div>
