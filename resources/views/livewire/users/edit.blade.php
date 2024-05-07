<?php

use App\Actions\DeleteCustomerAction;
use App\Models\Country;
use App\Models\User;
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

    #[Rule('required')]
    public ?int $country_id = null;

    #[Rule('nullable|image|max:1024')]
    public $avatar_file;

    public function mount(): void
    {
        $this->fill($this->user);
    }

    public function delete(): void
    {
        $action = new DeleteCustomerAction($this->user);
        $action->execute();

        $this->success('Deleted', redirectTo: '/users');
    }

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
        <x-button label="Delete patient" icon="o-trash" wire:click="delete" class="btn-error text-gray-100" wire:confirm="Are you sure you want to permanently delete this patient?" spinner responsive />
         <x-button label="Back" link="/users" icon="o-arrow-uturn-left" responsive/>
        </x-slot:actions>
    </x-header>

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
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
                    <x-input label="Address *" wire:model="address" />
                    {{-- <x-select label="Country" wire:model="country_id" :options="$countries" placeholder="Select Country" /> --}}
                </x-card>

               
                <x-card title="Demographics" class="mt-8" separator shadow>
                    <x-select label="Gender *" wire:model="gender"
                          :options="collect([['id' => 'male', 'name' => 'Male'], ['id' => 'female', 'name' => 'Female']])"
                          placeholder="Select gender"
                          icon=""/>
                    <br>
                    <x-input label="D.O.B *" wire:model="dob" />
                </x-card>

                <x-card title="Anthropometry" class="mt-8" separator shadow>
                    <x-input label="Height (cm) *" wire:model="height" />
                    <br>
                    <x-input label="Weight (kg)*" wire:model="weight" />
                </x-card>
                
                <x-card title="Physiology" class="mt-8" separator shadow>
                    <x-input label="TDD *" wire:model="tdd" />
                    <br>
                    <x-input label="DIA *" wire:model="dia" />
                </x-card>
                
                <x-card title="Exercise Info" class="mt-8" separator shadow>
                    <x-input label="HRR" wire:model="hrr" />
                    <br>
                    <x-input label="Max heart rate" wire:model="mhr" />
                    
                </x-card>

                <x-slot:actions>
                    <x-button label="Cancel" link="/users" />
                    <x-button label="Save" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </div>
        {{-- <div>
            <img src="/images/edit-form.png" width="300" class="mx-auto" />
        </div> --}}
    </div>
</div>
