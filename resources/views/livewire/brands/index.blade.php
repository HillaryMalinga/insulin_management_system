<?php

use App\Actions\DeleteCustomerAction;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component {
    use Toast, WithFileUploads;

    public User $user;

    #[Rule('required')]
    public string $name = '';

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|string')]
    public $gender = null;

    #[Rule('required|date')]
    public string $dob;

    #[Rule('required|numeric')]
    public float $height;

    #[Rule('required|numeric')]
    public float $weight;

    #[Rule('required|string')]
    public string $address;

    #[Rule('nullable|image|max:1024')]
    public $avatar_file;

    public function mount(): void
    {
        $this->user = User::find(Auth::user()->id);
        $this->fill($this->user);
        $this->gender = $this->user->patientInformation->first()->gender;
        $this->dob = $this->user->patientInformation->first()->dob;
        $this->height = $this->user->patientAthrometric->first()->height;
        $this->weight = $this->user->patientAthrometric->first()->weight;
        $this->address = $this->user->patientInformation->first()->address;
    }

//    public function delete(): void
//    {
//        $action = new DeleteCustomerAction($this->user);
//        $action->execute();
//
//        $this->success('Deleted', redirectTo: '/users');
//    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        // Update
        $this->user->update($data);

        if ($this->avatar_file) {
            $url = $this->avatar_file->store('users', 'public');
            $this->user->update(['avatar' => "/storage/$url"]);
        }

        $this->success('Customer updated with success.', redirectTo: '/users');
    }

    public function with(): array
    {
        return [
            'countries' => Country::all(),
        ];
    }
}; ?>

<div>
    <x-header :title="$user->name" separator progress-indicator>
        <x-slot:actions>
            {{--            <x-button label="Delete" icon="o-trash" wire:click="delete" class="btn-error" wire:confirm="Are you sure?"--}}
            {{--                      spinner responsive/>--}}
        </x-slot:actions>
    </x-header>

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <h3 class="text-base font-semibold leading-6 text-gray-900">Personal Details</h3>
            <hr>
            <br>
            <x-form wire:submit="save">
                <x-file label="Avatar" wire:model="avatar_file" accept="image/png, image/jpeg"
                        hint="Click to change | Max 1MB" crop-after-change>
                    <img src="{{ $user->avatar ?? '/images/empty-user.jpg' }}" class="h-40 rounded-lg mb-3"/>
                </x-file>

                <x-input label="Name" wire:model="name"/>
                <x-input label="Email" wire:model="email"/>

                <x-select label="Gender" wire:model="gender"
                          :options="collect([['id' => 'male', 'name' => 'Male'], ['id' => 'female', 'name' => 'female']])"
                          placeholder="---"
                          icon=""/>

                <x-input label="Date Of Birth" type="date" wire:model="dob" icon="" required/>

                {{--                <div class="grid grid-cols-2 gap-4 content-start">--}}
                {{--                    <x-input label="Height (m)" type="number" wire:model="height" step="0.001" icon="o-hand-raised"--}}
                {{--                             required/>--}}
                {{--                    <x-input label="Weight (kg)" type="number" wire:model="weight" step="0.001" icon="o-scale"--}}
                {{--                             required/>--}}
                {{--                </div>--}}

                <x-textarea label="Address" wire:model="address" rows="3" required/>

                <x-slot:actions>
                    <x-button label="Cancel" link="/users"/>
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </div>
        <div>
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-900">Demographics</h3>
                <hr>
                <br>
                <br>
                <div class="grid grid-cols-2 gap-4 content-start">
                    <x-input label="Age (years)" type="number" wire:model="height" step="0.001" icon=""
                             readonly/>
                    <x-input label="Gender" wire:model="weight" step="0.001" icon=""
                             readonly/>
                </div>
            </div>
            <br>
            <br>
            <br>

            <div class="mt-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Anthropometry</h3>
                <hr>
                <br>
                <div class="grid grid-cols-2 gap-4 content-start">
                    <x-input label="Height (m)" type="number" wire:model="height" step="0.001" readonly icon=""
                             required/>
                    <x-input label="Weight (kg)" type="number" wire:model="weight" step="0.001" readonly icon=""
                             required/>
                </div>
            </div>

            <br>
            <div class="mt-5">
                <h3 class="text-base font-semibold leading-6 text-gray-900">V02 max</h3>
                <hr>
                <br>
                <div class="gap-4 content-start">
                    <x-input label="HRR" wire:model="name"/>
                </div>
            </div>
        </div>
    </div>
</div>
