<x-app-layout>
    <x-slot name="title">Log in</x-slot>

    <div class="flex flex-col gap-6 max-w-md mx-auto mt-12 p-6 bg-white dark:bg-[#161615] rounded-lg shadow">
        @if (session('status'))
            <div class="mb-4 text-center text-sm font-medium text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
            @csrf
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <x-label for="email">Email address</x-label>
                    <x-input id="email" type="email" name="email" required autofocus autocomplete="email" placeholder="email@example.com" />
                    <x-input-error :message="$errors->first('email')" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <x-label for="password">Password</x-label>
                        @if (Password::canResetPassword())
                            <a href="{{ route('password.request') }}" class="text-sm text-primary underline">Forgot password?</a>
                        @endif
                    </div>
                    <x-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Password" />
                    <x-input-error :message="$errors->first('password')" />
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember" class="flex items-center space-x-3 text-sm">
                        <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary">
                        <span>Remember me</span>
                    </label>
                </div>

                <x-button type="submit" class="mt-4 w-full">
                    Log in
                </x-button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-sm text-primary underline">Sign up</a>
            </div>
        </form>
    </div>
</x-app-layout>
