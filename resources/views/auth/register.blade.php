<x-app-layout>
    <x-slot name="title">Register</x-slot>

    <div class="flex flex-col gap-6 max-w-md mx-auto mt-12 p-6 bg-white dark:bg-[#161615] rounded-lg shadow">
        @if (session('status'))
            <div class="mb-4 text-center text-sm font-medium text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-6">
            @csrf
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <x-label for="name">Name</x-label>
                    <x-input id="name" type="text" name="name" required autofocus autocomplete="name" placeholder="Full name" value="{{ old('name') }}" />
                    <x-input-error :message="$errors->first('name')" />
                </div>

                <div class="grid gap-2">
                    <x-label for="email">Email address</x-label>
                    <x-input id="email" type="email" name="email" required autocomplete="email" placeholder="email@example.com" value="{{ old('email') }}" />
                    <x-input-error :message="$errors->first('email')" />
                </div>

                <div class="grid gap-2">
                    <x-label for="password">Password</x-label>
                    <x-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Password" />
                    <x-input-error :message="$errors->first('password')" />
                </div>

                <div class="grid gap-2">
                    <x-label for="password_confirmation">Confirm password</x-label>
                    <x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm password" />
                    <x-input-error :message="$errors->first('password_confirmation')" />
                </div>

                <x-button type="submit" class="mt-2 w-full">
                    Create account
                </x-button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <a href="{{ route('login') }}" class="underline underline-offset-4 text-primary">Log in</a>
            </div>
        </form>
    </div>
</x-app-layout>
