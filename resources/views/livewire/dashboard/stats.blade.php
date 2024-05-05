<?php

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public string $period = '-30 days';

    public User $user;

    public function mount(): void
    {
        $this->user = auth()->user()->load('activity');
    }

    // General statistics
    public function stats(): array
    {
        $averageSugarLevel = 0;
        $lastSugarLevel = 0;
        $lastActivity = '';
        $healthClass = '';

        if (!$this->user->is_admin) {
            $averageSugarLevel = $this->user->activity()->avg('sugar_level') ?? 0;

            $lastSugarLevel = $this->user->activity()->latest()->first()->sugar_level ?? " ";

            list($lastActivity, $non) = explode(' ', $this->user->activity()->latest()->first()->protocol ?? " ");

            $healthClass = $this->user->patientAthrometric()->latest()->first()->bmi_category ?? "Not Set";
        } else {
            $averageSugarLevel = User::where('role', 'patient')->count();
            $lastSugarLevel = User::whereRelation('patientInformation', 'class', 'adult')->count();
            $lastActivity = User::whereRelation('patientInformation', 'class', 'adolescent')->count();
            $healthClass = 0;
        }
        return [
            'gross' => !$this->user->is_admin?number_format($averageSugarLevel, 2) . ' mg/dL' : $averageSugarLevel,
            'orders' => $lastSugarLevel .(!$this->user->is_admin? ' mg/dL' : ""),
            'newCustomers' => $lastActivity,
            'healthClass' => $healthClass
        ];
    }


    public function with(): array
    {
        return [
            'stats' => $this->stats(),
        ];
    }
}; ?>

<div>

    <div class="grid lg:grid-cols-4 gap-5 lg:gap-8">
        @if(!$user->is_admin)
            <x-stat :value="$stats['gross']" title="Average Sugar Level" icon=""
                    class="shadow truncate text-ellipsis"/>
            <x-stat :value="$stats['orders']" title="Last Sugar level" icon="" class="shadow"/>
            <x-stat :value="$stats['newCustomers']" title="Last Activity" icon="" class="shadow"/>
            <x-stat :value="$stats['healthClass']" title="Health Class" icon="" color="!text-pink-500"
                    class="shadow"/>
        @else
            <x-stat :value="$stats['gross']" title="Total Patients" icon="" class="shadow text-ellipsis"/>
            <x-stat :value="$stats['orders']" title="Hypoglycemic" icon="" class="shadow"/>
            <x-stat :value="$stats['newCustomers']" title="Hyperglycemic" icon="" class="shadow"/>
            <x-stat :value="0" title="Critical levels" icon="" color="!text-pink-500" class="shadow"/>
        @endif
    </div>
</div>
