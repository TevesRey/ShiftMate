<x-app-layout>
    <x-slot name="title">Forgot password</x-slot>

    <div class="flex flex-col gap-6 max-w-md mx-auto mt-12 p-6 bg-white dark:bg-[#161615] rounded-lg shadow">
        @if (session('status'))
            <div class="mb-4 text-center text-sm font-medium text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf
            <div class="grid gap-2">
                <x-label for="email">Email address</x-label>
                <x-input id="email" type="email" name="email" autocomplete="off" required autofocus placeholder="email@example.com" />
                <x-input-error :message="$errors->first('email')" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <x-button type="submit" class="w-full">
                    Email password reset link
                </x-button>
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Or, return to</span>
            <a href="{{ route('login') }}" class="underline underline-offset-4 text-primary">log in</a>
        </div>
    </div>
</x-app-layout>
