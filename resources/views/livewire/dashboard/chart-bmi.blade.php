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

    public array $bmiChart; // Remove initialization here

    public function __construct()
    {
        parent::__construct();

        // Initialize the $bmiChart property here
        $this->bmiChart = $this->initializeBMIChart();
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

    public function initializeBMIChart(): array
    {
        // Generate random BMI data
        $randomBMIData = $this->generateRandomBMIDataForLast30Days();

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
                'labels' => $randomBMIData['labels'],
                'datasets' => [
                    [
                        'label' => 'BMI',
                        'data' => $randomBMIData['data'],
                        'backgroundColor' => 'oklch(0.582743 0.256879 302.237915 /1)',
                        'borderWidth' => 1,
                        'borderColor' => 'oklch(0.582743 0.256879 302.237915 /1)',
                    ]
                ]
            ]
        ];
    }

//    RANDOM BMI DATA
    public function generateRandomBMIDataForLast30Days(): array
{
    $labels = [];
    $data = [];

    // Generate labels for the last 30 days
    for ($i = 29; $i >= 0; $i--) {
        $day = now()->subDays($i)->format('d-m-y');
        $labels[] = $day;
    }

    // Generate random BMI data for each day
    foreach ($labels as $day) {
        // For demo purposes, generate random BMI levels (between 18 and 30)
        $bmi = mt_rand(1800, 3000) / 100;
        $data[] = $bmi;
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
}

     

}; ?>

<div>
    <x-card title="BMI" separator shadow>
        <x-chart wire:model="bmiChart" class="h-44"/>
    </x-card>
</div>
