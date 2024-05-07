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
        if ((auth()->user()->role === 'patient')){
        $this->gender = $this->user->patientInformation->first()->gender;
        $this->dob = $this->user->patientInformation->first()->dob;
        $this->height = $this->user->patientAthrometric->first()->height;
        $this->weight = $this->user->patientAthrometric->first()->weight;
        $this->address = $this->user->patientInformation->first()->address;
        }
       
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
    <x-header title="Settings" separator progress-indicator>
    </x-header>
    <div class="grid gap-5 lg:grid-cols-2">
        <x-form wire:submit="save">
                <x-card title="Personal Info" separator shadow>
                    <x-file label="Profile picture" wire:model="avatar_file" accept="image/png, image/jpeg" hint="Click to change | Max 1MB" crop-after-change>
                        <img src="{{ $user->avatar ?? '/images/empty-user.jpg' }}" class="h-40 rounded-lg mb-3" />
                    </x-file>
                    <br>
                    <x-input label="Name" wire:model="name" />
                    <br>
                    <x-input label="Phone *" wire:model="phone" />
                    <br>
                    <x-input label="Email" wire:model="email" />
                    <br>
                    <x-input label="Address" wire:model="address" />
                    {{-- <x-select label="Country" wire:model="country_id" :options="$countries" placeholder="Select Country" /> --}}
                </x-card>

                <x-card title="Password" class="mt-8" separator shadow>
                    <x-input label="Enter current password" wire:model="password" />
                    <br>
                    <x-input label="Enter new password" wire:model="newPassword" />
                    <br>
                    <x-input label="Confirm new password" wire:model="confirmPassword" />
                </x-card>

               @if(auth()->user()->role === 'patient')
                    <x-card title="Demographics" class="mt-8" separator shadow>
                        <x-select label="Gender" wire:model="gender"
                            :options="collect([['id' => 'male', 'name' => 'Male'], ['id' => 'female', 'name' => 'female']])"
                            placeholder="Select gender"
                            icon=""/>
                        <br>
                        <x-input label="D.O.B" wire:model="dob" />
                    </x-card>

                    <x-card title="Anthropometry" class="mt-8" separator shadow>
                        <x-input label="Height (cm)" wire:model="height" />
                        <br>
                        <x-input label="Weight (kg)" wire:model="weight" />
                    </x-card>
                    
                    {{-- <x-card title="Physiology" class="mt-8" separator shadow>
                        <x-input label="TDD *" wire:model="tdd" />
                        <br>
                        <x-input label="DIA *" wire:model="dia" />
                    </x-card> --}}
                    
                    <x-card title="Exercise Info" class="mt-8" separator shadow>
                        <x-input label="HRR" wire:model="hrr"/>
                        <br>
                        <x-input label="Max heart rate" wire:model="mhr" />
                        <br>
                        <p class="text-xs">Don't know how to determine HRR or MHR?  <span class="underline cursor-pointer text-primary">Click here</span></p>
                    </x-card>
                @endif
                    <x-slot:actions>
                        <x-button label="Cancel" link="/users" />
                        <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                    </x-slot:actions>
                
            </x-form>

            @if(auth()->user()->role === 'admin')
               <div>
                    <x-card title="Admin settings" separator shadow>
                    </x-card>
                </div> 
            @endif  
            
            
        {{-- <x-card title="Personal Details" separator shadow>
            <x-form wire:submit="save">
                <x-file label="Avatar" wire:model="avatar_file" accept="image/png, image/jpeg"
                        hint="Click to change | Max 1MB" crop-after-change>
                    <img src="{{ $user->avatar ?? '/images/empty-user.jpg' }}" class="h-40 rounded-lg mb-3"/>
                </x-file>

                <x-input label="Name" wire:model="name"/>
                
                <x-input label="Phone" wire:model="phone"/>

                <x-input label="Email" wire:model="email"/>

                <x-select label="Gender" wire:model="gender"
                          :options="collect([['id' => 'male', 'name' => 'Male'], ['id' => 'female', 'name' => 'female']])"
                          placeholder="Select gender"
                          icon=""/>

                <x-input label="Date Of Birth" type="date" wire:model="dob" icon="" required/>
                <x-textarea label="Address" wire:model="address" rows="3" required/>

                <x-slot:actions>
                    <x-button label="Cancel" link="/users"/>
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </x-card>
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
        </div> --}}
    </div> 
</div>
