<x-app-layout>
    <x-container>
        <x-section class="mb-6">
            @include('profile.partials.update-profile-information-form')
        </x-section>

        <x-section class="mb-6">
            @include('profile.partials.update-password-form')
        </x-section>

        <x-section class="mb-6">
            @include('profile.partials.delete-user-form')
        </x-section>
    </x-container>
</x-app-layout>
