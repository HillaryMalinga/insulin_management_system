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
            $averageSugarLevel = $this->user->activity()->avg('sugar_level');

            $lastSugarLevel = $this->user->activity()->latest()->first()->sugar_level;

            list($lastActivity, $non) = explode(' ', $this->user->activity()->latest()->first()->protocol);

            $healthClass = $this->user->patientAthrometric()->latest()->first()->bmi_category;
        } else {
            $averageSugarLevel = User::where('role', 'patient')->count();
            $lastSugarLevel = User::whereRelation('patientInformation', 'class', 'adult')->count();
            $lastActivity = User::whereRelation('patientInformation', 'class', 'adolescent')->count();
            $healthClass = User::whereRelation('activity', 'class', 'adult')->latest()->first()->bmi_category;
        }
        return [
            'gross' => number_format($averageSugarLevel, 2) . ' mg/dL',
            'orders' => $lastSugarLevel . ' mg/dL',
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
            <x-stat :value="$stats['gross']" title="Average Sugar Level" icon="o-banknotes"
                    class="shadow truncate text-ellipsis"/>
            <x-stat :value="$stats['orders']" title="Last Sugar level" icon="o-gift" class="shadow"/>
            <x-stat :value="$stats['newCustomers']" title="Last Activity" icon="o-user-plus" class="shadow"/>
            <x-stat :value="$stats['healthClass']" title="Health Class" icon="o-heart" color="!text-pink-500"
                    class="shadow"/>
        @else
            <x-stat :value="$stats['gross']" title="Total Patients" icon="o-banknotes" class="shadow truncate text-ellipsis"/>
            <x-stat :value="$stats['orders']" title="Total Adults" icon="o-gift" class="shadow"/>
            <x-stat :value="$stats['newCustomers']" title="Total Adolescents" icon="o-user-plus" class="shadow"/>
            <x-stat :value="0" title="Most Activity" icon="o-heart" color="!text-pink-500" class="shadow"/>
        @endif
    </div>
</div>
