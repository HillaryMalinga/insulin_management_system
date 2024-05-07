<?php

use App\Actions\DeleteCustomerAction;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component {
    use Toast, WithFileUploads;

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|string')]
    public $gender = null;

    #[Rule('required|date')]
    public string $dob = '';

    #[Rule('required|numeric')]
    public float $height = 0.0;

    #[Rule('required|numeric')]
    public float $weight = 0.0;

    #[Rule('required|string')]
    public string $address = '';

    #[Rule('nullable|image|max:1024')]
    public $avatar_file;

    public function save(): void
    {
        // Validate
        $data = $this->validate();
//        dd($data);

        // Create User
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => '/images/empty-user.jpg',
            'password' => Hash::make('password'),
            'country_id' => 1,
            'email_verified_at' => now(),
        ]);

        //patient info
        $user->patientInformation()->create([
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'class' => Carbon\Carbon::parse($data['dob'])->age < 18 ? 'adolescent' : 'adult',
            'address' => $data['address'],
        ]);

        $bmi = $data['weight'] / ($data['height'] * $data['height']);

        $bmi_category = match (true) {
            $bmi <= 18 => 'Underweight',
            $bmi > 18 && $bmi <= 25 => 'Normal weight',
            $bmi > 25 && $bmi <= 30 => 'Overweight',
            default => 'Obese',
        };

        //patient athrometric
        $user->patientAthrometric()->create([
            'height' => $data['height'],
            'weight' => $data['weight'],
            'bmi' => $bmi,
            'bmi_category' => $bmi_category,
        ]);

        $gender =  $data['gender'];
        $heightCubed = $data['height'] * $data['height'] * $data['height'];
        $tbv = ($gender == 'male') ? (0.3669 * $heightCubed)
            + (0.03219 *  $data['weight'])
            + 0.6041 : (0.3561 * $heightCubed)
            + (0.03308 *  $data['weight'])
            + 0.1833;

        $user->patientPhysiology()->create([
            // 'tbv' => round($tbv, 5),
            'tbv' => '2',
            'cbgr' => round((0.00556 / (0.6 * $tbv)), 5),
            'isf' => 2,
            'dia' => 0.5,
        ]);

        if ($this->avatar_file) {
            $url = $this->avatar_file->store('users', 'public');
            $user->update(['avatar' => "/storage/$url"]);
        }

        $this->success('Patient created with success.', redirectTo: '/users');
    }

};
?>

<div>
    <x-header title="New Patient" separator progress-indicator/>

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <x-form wire:submit="save">
                <x-file label="Profile picture" wire:model="avatar_file" accept="image/png, image/jpeg"
                        hint="Click to change | Max 1MB" crop-after-change>
                    <img src="{{ $user->avatar ?? '/images/empty-user.jpg' }}" class="h-40 rounded-lg mb-3"/>
                </x-file>
                <x-input label="Name" wire:model="name" icon="o-user" required/>
                
                <x-input label="Phone" wire:model="phone" icon="o-phone" required/>

                <x-input label="Email" wire:model="email" icon="o-at-symbol" required/>

                <x-select label="Gender" wire:model="gender"
                          :options="collect([['id' => 'male', 'name' => 'Male'], ['id' => 'female', 'name' => 'Female']])"
                          placeholder="Select gender"
                          icon="o-user-plus"/>

                <x-input label="Date Of Birth" type="date" wire:model="dob" icon="o-calendar" required/>

                <div class="grid grid-cols-2 gap-4 content-start">
                    <x-input label="Height (m)" type="number" wire:model="height" step="0.001" icon="o-hand-raised"
                             required/>
                    <x-input label="Weight (kg)" type="number" wire:model="weight" step="0.001" icon="o-scale"
                             required/>
                </div>

                <x-textarea label="Address" wire:model="address" rows="3" required/>
                <x-slot:actions>
                    <x-button label="Cancel" link="/users"/>
                    <x-button label="Create" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </div>
        {{-- <div>
            <img src="/images/edit-form.png" width="300" class="mx-auto"/>
        </div> --}}
    </div>
</div>
