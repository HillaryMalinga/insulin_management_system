<?php

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PatientActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public ?string $user_id = null;
    public ?string $plan_exercise_id = null;
    public ?string $plan_exercise_time = null;
    public ?string $plan_exercise_intensity = null;
    public ?string $plan_exercise_duration = null;
    public ?string $un_plan_exercise_time = null;
    public ?string $un_plan_exercise_intensity = null;
    public string $save = '';

    public float $exercise_dose = 0.00;
    public float $snack_size = 0.00;

    public ?int $confirm = null;
    public float $dosage1 = 10.00;
    public float $intensity = 0.00;
    public float $sugar_level = 0.00;
    public float $number_carbs = 0.00;
    public float $foodInsulinDos = 0.00;

    public int $currentTimeCounter = 0;
    public int $timeLimit = 1;

    public bool $timer = false;
    public bool $initCard = true;
    public bool $abortEx = false;

    public string $abort_message = '';
    public float $blood_sugar_expected = 6.00;
    public float $csnack = 0.00;
    public bool $postUnplannedEx = false;

    public string $displayTime = '';

    public Collection $protocols;

    public float $plannedExerciseIntensity = 360.00;
    public string $lastActivityTime;

    public bool $first = false;
    public bool $second = false;

    public bool $showActivitiesProtocol = false;

    //the mount method is called when the component is initialized
    //it is used to set initial values for the component's properties
    public function mount(): void
    {
        $this->search();
        $this->updateProtocol();
    }

    //set up the protocols for the user to select
    //for now we have `exercise` and `foodInsulin` and `hypoglycaemia`
    public function updateProtocol(): Collection
    {
        return collect([
            [
                'id' => 'exercise',
                'name' => 'About to exercise',
                'description' => 'Exercise Protocol Description',
                'status' => 'active'
            ],
            [
                'id' => 'foodInsulin',
                'name' => 'About to eat',
                'description' => 'Food Insulin Protocol Description',
                'status' => 'active'
            ]
        ]);
    }

    public function search(string $value = ''): void
    {
        $this->protocols = $this->updateProtocol();
    }

    public function confirmSave()
    {

        if ($this->save !== '') {
            $this->error('Please select an activity', redirectTo: "/orders/create");
        }

        $sugar = $this->sugar_level;
        if ($sugar > 0) {

            // Food Insulin Protocol
//            if ($this->save === 'foodInsulin') {
//                $this->createActivity('Food Insulin', 'Food Insulin , Amount of Carbs to be Eaten ' . $this->number_carbs, $sugar);
//            }

            //planned exercise
            if ($this->save === 'first|exercise') {
                $this->createActivity('Exercise', 'Exercise Planned Session Less than 120 mins', $sugar);
            }

            if ($this->save === 'second|exercise') {
                $this->createActivity('Exercise', 'Exercise Planned Session Greater than 120 mins, Less 360 mins', $sugar);
            }

            if ($this->save === 'third|exercise') {
                $this->createActivity('Exercise', 'Vigorous Exercise Planned Session Greater than 360 mins', $sugar);
            }

        } else {
            $this->error('Please enter blood sugar level', redirectTo: "/orders/create");
        }

        $this->success('Entry Saved!', redirectTo: "/orders/create");
    }

    public function createActivity($protocol, $protocol_desc, $sugar_level, $carbs = null, $snack_size = null): void
    {
        DB::table('patient_activities')->insert(
            [
                'patient_id' => Auth::user()->id,
                'protocol' => $protocol . ' Protocol',
                'activity_description' => $protocol_desc,
                'sugar_level' => $sugar_level,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
                'carbs' => $carbs,
                'snack_size' => $snack_size,
            ]);

        DB::table('patient_activity_histories')->insert(
            [
                'patient_id' => Auth::user()->id,
                'action' => $protocol . ' Protocol',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
    }

    public function updatedPlanExerciseIntensity($value): void
    {
        $this->intensity = match ($value) {
            'very_light' => 25.00,
            'light' => 30.00,
            'moderate' => 50.00,
            'vigorous' => 70.0,
            'very_vigorous' => 90.00
        };

        if ($value == 'very_vigorous') {
            $this->confirm = 1;
            $this->save = 'third|exercise';
        }
    }

    #[NoReturn]
    public function calculateDos(): void
    {
        $this->foodInsulinDos = $this->number_carbs / 10;

//        d1 = (f0.[1-(T1-T0-15)/v])/w + (i - x)/(a.w) +
//            f1/w - [1 - (T1-T0)/p].d0

//        $this->confirm = 1;

        $this->save = 'foodInsulin';

        //save activity insulin
        if ($this->sugar_level > 0) {
            $this->createActivity(
                'Food Insulin', 'Food Insulin , Amount of Carbs to be Eaten ' . $this->number_carbs,
                $this->sugar_level,
                $this->number_carbs
            );

            $this->success('Activity Saved!');
        } else {
            $this->error('Please enter sugar level');
        }
    }

    #[NoReturn]
    public function updatedUnPlanExerciseIntensity($value): void
    {
        $patient = Auth::user()?->patientAthrometric->first();

        if (!$patient) {
            $this->error('No Patient Details Found, Please Contact Your Doctor', redirectTo: "/orders/create");
        }

        if ($value == 'very_light' || $value == 'light' || $value == 'moderate' || $value == 'vigorous' || $value == 'very_vigorous') {
//            $this->confirm = 1;

            if ($value == 'very_light') {
                $this->snack_size = (0.25 * $patient?->weight) * 0.5;
            } elseif ($value == 'light') {
                $this->snack_size = (0.5 * $patient->weight) * 0.5;
            } elseif ($value == 'vigorous' || $value == 'moderate' || $value == 'very_vigorous') {
                $this->snack_size = (1 * $patient->weight) * 0.5;
            } else {
                $this->snack_size = 0.00;
            }

            $this->createActivity(
                'Exercise', 'Unplanned Exercise ,' . Str::title($value) . ' Intensity .' . 'Requires Snack Size' . $this->snack_size,
                $this->sugar_level,
                null,
                $this->snack_size
            );

            $this->timer = true;
            $this->success('Activity Saved!, Recheck Sugar Level After 30 mins');

        } else {
            $this->confirm = null;
        }
    }

    //when the user selects the time to start the session
    //then we calculate the range of session depending with the set by doctor's time

    #[NoReturn]
    public function updatedPlanExerciseTime($value): void
    {
        $time = Carbon::parse(now()->format('Y-m-d') . ' ' . $value . ':00');
        $this->lastActivityTime = Carbon::parse(PatientActivity::orderBy('created_at', 'desc')->first()->created_at)->subtract($time)->minute;

        $this->first = false;
        $this->second = false;

        if ($this->lastActivityTime >= 0 && $this->lastActivityTime <= 120) {
            // 0 < (t1 - t0) < 120

            $this->confirm = 1;
            $this->save = 'first|exercise';
            $this->first = true;
        } else if ($this->lastActivityTime > 120 && $this->lastActivityTime <= ($this->plannedExerciseIntensity - 60)) {
            //120mins < (t1 - t0) < (p - 60)

            $this->user_id = "foodInsulin";
            $this->second = true;
        } else if ($this->lastActivityTime > $this->plannedExerciseIntensity) {
            //(t1 - t0) > p

        } else {
            $this->confirm = null;
            $this->save = '';
            $this->user_id = null;
        }
    }

    #[NoReturn]
    public function updatedPlanExerciseDuration($value): void
    {
        if ($value == 'less30' || $value == 'above30') {
            $this->confirm = 1;
            $this->save = 'second|exercise';

            if ($value == 'less30') {
                $this->exercise_dose = (100 - 30) * $this->dosage1;
            } else {
                $this->exercise_dose = (100 - 60) * $this->dosage1;
            }

        } else {
            $this->confirm = null;
            $this->save = '';
        }
    }

    public function createTimer(): void
    {
        if ($this->currentTimeCounter >= $this->timeLimit * 60) {
            $this->timer = false;
            $this->currentTimeCounter = 0;
            $this->displayTime = "Time's up!";

            $this->postUnplannedEx = true;
            $this->initCard = false;
        } else {
            $minutes = floor($this->currentTimeCounter / 60);
            $seconds = $this->currentTimeCounter % 60;
            $this->displayTime = '00 : ' . ($minutes < 10 ? '0' : '') . $minutes . ' : ' . ($seconds < 10 ? '0' : '') . $seconds;
            $this->currentTimeCounter++;
        }
    }

    public function recheckSugar(): void
    {
        $this->abortEx = false;
        if ($this->sugar_level < 4) {
            $this->abortEx = true;
            $this->abort_message = 'Sugar Level Too Low, Start Activity With Hypoglycaemia';
            $this->createActivity(
                'Exercise', 'Unplanned Exercise , ' . $this->abort_message,
                $this->sugar_level,
                null,
                0.00
            );
        } elseif ($this->sugar_level > 4 && $this->sugar_level <= $this->blood_sugar_expected) {
            $this->csnack = ($this->blood_sugar_expected - $this->sugar_level) / 2;
            $this->createActivity(
                'Exercise', 'Unplanned Exercise , Exercise To Be Done With Snack Size ' . $this->csnack,
                $this->sugar_level,
                null,
                $this->csnack
            );
        } elseif ($this->sugar_level > $this->blood_sugar_expected) {
            $this->abortEx = true;
            $this->abort_message = 'Sugar Level Too Low, End Physical Activity';

            $this->createActivity(
                'Exercise', 'Unplanned Exercise , ' . $this->abort_message,
                $this->sugar_level,
                null,
                0.00
            );
        }
    }

    //show the activities when sugar level is set
    public function confirmSugarLevel(): void
    {
        $this->showActivitiesProtocol = false;
        if ($this->sugar_level > 0) {
            $this->showActivitiesProtocol = true;
        }
    }
}; ?>

<div>
    <x-header title="Entries" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Back" link="/orders" icon="o-arrow-uturn-left" responsive/>
        </x-slot:actions>
    </x-header>

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- CUSTOMER --}}
        <div class="content-start">
            @if($initCard)
                <br>
                <x-card title="New Entry" separator shadow
                        progress-indicator="confirm">
                    <x-slot:menu>
                        <x-button label="Reset" link="/orders/create" icon="o-arrow-uturn-right"
                                  class="btn-sm btn-danger"/>
                    </x-slot:menu>
                    <x-slot:actions>
                        @if($confirm)
                            <x-button label="Confirm & set reminder" wire:click="confirmSave" icon="o-check"
                                      class="btn-sm btn-primary"/>
                        @endif
                    </x-slot:actions>

                    <x-input label="Enter blood sugar level (mmol/L)" wire:model="sugar_level" type="number" step="0.01"
                             icon=""/>

                    <br>

                    <x-button label="Confirm Sugar Level & Select Activity" wire:click="confirmSugarLevel"
                              icon="o-check"
                              class="btn-sm btn-primary"/>
                    <br> <br>

                    @if($showActivitiesProtocol)
                        <x-choices
                            label="Select activity"
                            wire:model.live="user_id"
                            :options="$protocols"
                            option-sub-label="email"
                            {{-- hint="Search for Protocol name" --}}
                            icon=""
                            single
                            searchable
                        />

                        @if($user_id === "exercise")
                            <br>
                            <x-choices
                                wire:model.live="plan_exercise_id"
                                :options="collect([['id'=>'planned', 'name'=>'The Exercise Is Planned'], ['id'=>'unplanned', 'name'=>'The Exercise Is Unplanned (Not Planned)']])"
                                option-sub-label="email"
                                {{-- hint="Select Exercise Plan" --}}
                                label="Select exercise type"
                                {{-- icon="o-magnifying-glass" --}}
                                single
                                searchable/>
                        @endif

                        {{--for planned exercize the patient to select the time to start the session--}}
                        @if($plan_exercise_id === "planned")
                            <br>
                            <x-input label="Select Time To Start Session"
                                     wire:model.live="plan_exercise_time"
                                     type="time"
                                     icon=""/>
                            {{--                        <x-choices--}}
                            {{--                            wire:model.live="plan_exercise_time"--}}
                            {{--                            :options="collect([--}}
                            {{--                         ['id'=>'first', 'name'=>'Session is less than 120 mins'],--}}
                            {{--                         ['id'=>'second', 'name'=>'Session is greater than 120 mins, Less 360 mins'],--}}
                            {{--                         ['id'=>'third', 'name'=>'Session is greater 360 mins']--}}
                            {{--                         ])"--}}
                            {{--                            option-sub-label="email"--}}
                            {{--                             hint="Select Time For Session"--}}
                            {{--                            label="Enter start time"--}}
                            {{--                             icon="o-magnifying-glass"--}}
                            {{--                            single--}}
                            {{--                            searchable/>--}}
                        @endif

                        @if($plan_exercise_id === "unplanned")
                            <br>
                            <x-choices
                                wire:model.live="un_plan_exercise_time"
                                :options="collect([['id'=>'first', 'name'=>'Session is less than 120 mins'], ['id'=>'second', 'name'=>'Session is greater than 120 mins']])"
                                option-sub-label="email"
                                {{-- hint="Select Time For Session" --}}
                                label="Select session"
                                {{-- icon="o-magnifying-glass" --}}
                                single
                                searchable/>
                        @endif

                        @if($un_plan_exercise_time)
                            <br>
                            <x-choices
                                wire:model.live="un_plan_exercise_intensity"
                                :options="collect([
                                            ['id'=>'very_light', 'name'=>'Very Light'],
                                            ['id'=>'light', 'name'=>'Light'],
                                            ['id'=>'moderate', 'name'=>'Moderate'],
                                            ['id'=>'vigorous', 'name'=> 'Vigorous'],
                                            ['id'=> 'very_vigorous','name'=> 'Very Vigorous']
                                            ])"
                                option-sub-label="email"
                                {{-- hint="Select Exercise Intensity" --}}
                                label="Select exercise intensity"
                                {{-- icon="o-magnifying-glass" --}}
                                single
                            />
                        @endif

                        @if($un_plan_exercise_intensity === 'very_light')
                            {{--display snack size--}}
                            <br>
                            <x-stat :value="'Snack Size : '.$snack_size" title="Prescribed insulin dose"
                                    {{-- icon="o-banknotes" --}}
                                    class="shadow truncate text-ellipsis"/>
                        @endif

                        @if($un_plan_exercise_intensity === 'light')
                            {{--display snack size--}}
                            <br>
                            <x-stat :value="'Snack Size : '.$snack_size" title="Prescribed insulin dose"
                                    {{-- icon="o-banknotes" --}}
                                    class="shadow truncate text-ellipsis"/>
                        @endif

                        @if($un_plan_exercise_intensity == 'moderate' || $un_plan_exercise_intensity == 'vigorous' || $un_plan_exercise_intensity == 'very_vigorous')
                            {{--display snack size--}}
                            <br>
                            <x-stat :value="'Snack Size : ' . $snack_size"
                                    title="Prescribed insulin dose"
                                    {{-- icon="o-banknotes" --}}
                                    class="shadow truncate text-ellipsis"/>
                        @endif

                        @if($first)
                            {{--snack protocol--}}
                            <br>
                            <x-stat :value="'Snack Protocol 15 - 20 minutes before Session'"
                                    title="Prescribed insulin dose"
                                    {{-- icon="o-banknotes" --}}
                                    class="shadow text-ellipsis h-fit"
                            />
                        @endif

                        @if($second)
                            {{--snack protocol--}}
                            <br>
                            <x-choices
                                wire:model.live="plan_exercise_intensity"
                                :options="collect([
                               ['id'=>'very_light', 'name'=>'Very Light'],
                               ['id'=>'light', 'name'=>'Light'],
                               ['id'=>'moderate', 'name'=>'Moderate'], ['id'=>'vigorous', 'name'=> 'Vigorous'],
                               ['id'=> 'very_vigorous','name'=> 'Very Vigorous']])"
                                option-sub-label="email"
                                {{-- hint="Select Exercise Intensity" --}}
                                label="Select exercise intensity"
                                {{-- icon="o-magnifying-glass" --}}
                                single
                                searchable/>
                        @endif

                        {{--                @if($plan_exercise_time === 'third')--}}
                        {{--                    --}}{{--snack protocol--}}
                        {{--                    <x-stat :value="'Food Insulin Protocol'" title="Prescribed insulin dose"--}}
                        {{--                            icon="o-banknotes"--}}
                        {{--                            class="shadow truncate text-ellipsis"/>--}}
                        {{--                @endif--}}

                        @if($user_id === "foodInsulin")
                            <br>
                            <x-input label="Enter net carbs (grams)" wire:model="number_carbs"
                                     icon="" type="number" step="0.01"
                            >
                                <x-slot:append>
                                    <x-button label="Calculate Dosage" wire:click="calculateDos" icon="o-check"
                                              class="btn-primary rounded-l-none"/>
                                </x-slot:append>
                            </x-input>
                        @endif

                        @if($foodInsulinDos)
                            <br>
                            <x-stat :value="($foodInsulinDos) . ' ml'"
                                    title="Prescribed insulin dose"
                                    icon=""
                                    class="shadow truncate text-ellipsis"/>
                        @endif

                        @if($plan_exercise_intensity && $plan_exercise_intensity  != "very_vigorous")
                            <br>
                            <x-choices
                                wire:model.live="plan_exercise_duration"
                                :options="collect([['id'=>'less30', 'name'=>'Less Than 30 mins'], ['id'=>'above30', 'name'=>'Above 30mins']])"
                                option-sub-label="email"
                                {{-- hint="Select Exercise Duration" --}}
                                label="Select intended duration"
                                {{-- icon="o-magnifying-glass" --}}
                                single
                                searchable/>
                        @endif

                        @if($plan_exercise_intensity  == "very_vigorous")
                            <br>
                            <x-stat :value="($dosage1) . ' ml'"
                                    title="Prescribed insulin dose"
                                    {{-- icon="o-banknotes" --}}
                                    class="shadow truncate text-ellipsis"/>
                        @endif

                        @if($plan_exercise_duration === 'less30' || $plan_exercise_duration === 'above30')
                            <br>
                            @if($exercise_dose != 0.00)
                                <x-stat :value="($exercise_dose) . ' ml'"
                                        title="Prescribed insulin dose"
                                        {{-- icon="o-banknotes" --}}
                                        class="shadow truncate text-ellipsis"/>
                            @else
                                <x-stat :value="'Ooops! Something Went Wrong'" title="Prescribed insulin dose"
                                        {{-- icon="o-banknotes" --}}
                                        class="shadow truncate text-ellipsis"/>
                            @endif
                        @endif
                        <br>
                    @else
                        <x-stat :value="'To Begin Please Enter Sugar Level Above 0'" title="Enter Sugar Level"
                                {{-- icon="o-banknotes" --}}
                                class="shadow truncate text-ellipsis"/>
                    @endif

                </x-card>
            @endif

            @if($postUnplannedEx)
                <br>
                <x-card title="Protocol" separator shadow
                        progress-indicator="confirm">
                    <x-slot:menu>
                        <x-button label="Reset Action" link="/orders/create" icon="o-arrow-uturn-right"
                                  class="btn-sm btn-danger"/>
                    </x-slot:menu>

                    <x-input label="Enter Current Sugar Level" wire:model="sugar_level" type="number" step="0.01"
                             icon="o-user" required>
                        <x-slot:append>
                            {{-- Add `rounded-l-none` class --}}
                            <x-button label="Confirm Sugar" wire:click="recheckSugar" icon="o-check"
                                      class="btn-primary rounded-l-none"/>
                        </x-slot:append>
                    </x-input>
                </x-card>
            @endif

            @if($csnack > 0)
                <br>
                <x-card title="Information" subtitle="Snack Size" shadow separator>
                    <x-stat :value="'C Snack '. ($csnack)" title="Snack Size"
                            {{-- icon="o-banknotes" --}}
                            class="shadow truncate text-ellipsis"/>
                    <x-button label="Confirm" link="/orders/create" icon="o-face-frown" class="btn-danger"/>
                </x-card>
            @endif

            @if($abortEx)
                <br>
                <x-card title="Information" subtitle="Blood Sugar Test After 30mins" shadow separator>
                    {{ $abort_message }}
                    <x-button label="Abort Session" link="/orders/create" icon="o-face-frown" class="btn-danger"/>
                </x-card>
            @endif

            @if($timer)
                <br>
                <x-card title="Countdown Timer" shadow separator>
                    <div class="p-10 bg-white rounded-lg shadow-md">
                        <div class="flex flex-col items-center">
                            <div id="timer" class="text-4xl font-mono text-gray-800">
                                <div wire:poll="createTimer">
                                    {{ $displayTime }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <x-slot:actions>
                        <x-button label="Reset Clock" icon="o-arrow-uturn-left" responsive/>
                    </x-slot:actions>
                </x-card>
            @endif
        </div>

        {{-- IMAGE --}}
        <div>
            <img src="/images/edit-form.png" class="mx-auto" width="300px"/>
        </div>
    </div>

    {{--    <script>--}}
    {{--        let currentTimeCounter = {{ $currentTimeCounter }};--}}
    {{--        const timeLimit = {{ $timeLimit }}; // Set the time limit here (in minutes)--}}
    {{--        let finish = false;--}}

    {{--        const countTime = () => {--}}
    {{--            const timeLimitInMinutes = timeLimit * 60;--}}
    {{--            const timerDisplay = document.getElementById('timer');--}}

    {{--            const timeCounter = setInterval(() => {--}}
    {{--                if (currentTimeCounter >= timeLimitInMinutes) {--}}
    {{--                    clearInterval(timeCounter);--}}
    {{--                    finish = true;--}}
    {{--                    timerDisplay.textContent = "Time's up!";--}}
    {{--                } else {--}}
    {{--                    let minutes = Math.floor(currentTimeCounter / 60);--}}
    {{--                    let seconds = currentTimeCounter % 60;--}}
    {{--                    timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;--}}
    {{--                    currentTimeCounter++;--}}
    {{--                }--}}
    {{--            }, 1000);--}}
    {{--        };--}}
    {{--        countTime();--}}
    {{--    </script>--}}
</div>
