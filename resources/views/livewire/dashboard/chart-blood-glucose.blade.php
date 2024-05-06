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

    public array $chartGross = [
        // Chart Gross data...
    ];

    public array $bloodGlucoseChart; // Remove initialization here

    public function __construct()
    {
        parent::__construct();

        // Initialize the $bloodGlucoseChart property here
        $this->bloodGlucoseChart = $this->initializeBloodGlucoseChart();
    }

    public function with(): array
    {
        $this->refreshChartGross();

        return [];
    }

    #[Computed]
    public function refreshChartGross(): void
    {
        // Refresh chart gross data...
    }

    public function initializeBloodGlucoseChart(): array
    {
        // Generate random blood glucose data
        $randomBloodGlucoseData = $this->generateRandomBloodGlucoseDataForLast30Days();

        return [
            'type' => 'line',
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ]
                ],
            ],
            'data' => [
                'labels' => $randomBloodGlucoseData['labels'],
                'datasets' => [
                    [
                        'label' => 'Blood sugar',
                        'data' => $randomBloodGlucoseData['data'],
                        'backgroundColor' => 'oklch(0.582743 0.256879 302.237915 /1)',
                        'borderWidth' => 1,
                        'borderColor' => 'oklch(0.582743 0.256879 302.237915 /1)',
                    ]
                ]
            ]
        ];
    }

    // Generate random blood glucose data for the last 30 days
    public function generateRandomBloodGlucoseDataForLast30Days(): array
    {
        $labels = [];
        $data = [];

        // Generate labels for the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('d-m-y');
            $labels[] = $day;
        }

        // Generate random blood glucose data for each day (in mmol/L)
        foreach ($labels as $day) {
            // For demo purposes, generate random blood glucose levels within typical range (3.9 to 7.8 mmol/L)
            $bloodGlucose = mt_rand(39, 78) / 10; // Convert to mmol/L
            $data[] = $bloodGlucose;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

}; ?>

<div>
    <x-card title="Blood Glucose" separator shadow>
        <x-chart wire:model="bloodGlucoseChart" class="h-44"/>
    </x-card>
</div>

