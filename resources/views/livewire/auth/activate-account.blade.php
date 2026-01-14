<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
        @if($alreadyActivated)
            {{-- Already Activated --}}
            <div class="text-center py-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Already Activated</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    This account has already been activated. You can sign in with your credentials.
                </p>
                <a href="{{ route('login') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    Go to Sign In
                </a>
            </div>

        @elseif($expired)
            {{-- Expired Token --}}
            <div class="text-center py-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Activation Link Expired</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    This activation link has expired. Please contact an administrator to receive a new link.
                </p>
                <a href="mailto:support@popvox.org"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Contact Support
                </a>
            </div>

        @elseif(!$validToken)
            {{-- Invalid Token --}}
            <div class="text-center py-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Invalid Activation Link</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    This activation link is invalid or has already been used. Please check your email for the correct link or contact an administrator.
                </p>
                <a href="{{ route('login') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Go to Sign In
                </a>
            </div>

        @else
            {{-- Valid Token - Show Password Form --}}
            <div>
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Welcome, {{ $user->name }}!</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Set your password to activate your account</p>
                </div>

                <form wire:submit="activate" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" id="email" value="{{ $user->email }}" disabled
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 dark:bg-gray-700 dark:border-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" id="password" wire:model="password" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Choose a secure password">
                        @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                        <input type="password" id="password_confirmation" wire:model="password_confirmation" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Confirm your password">
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Activate Account
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>


