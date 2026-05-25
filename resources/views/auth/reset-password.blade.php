<x-layouts.guest>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <div style="margin-bottom:20px;">
            <x-ui.input type="email" name="email" label="Email" :value="old('email', request()->email)" :error="$errors->first('email')" required autofocus autocomplete="email" />
        </div>

        <div style="margin-bottom:20px;">
            <x-ui.input type="password" name="password" label="New Password" :error="$errors->first('password')" required autocomplete="new-password" />
        </div>

        <div style="margin-bottom:20px;">
            <x-ui.input type="password" name="password_confirmation" label="Confirm Password" required autocomplete="new-password" />
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Reset Password
        </x-ui.button>
    </form>
</x-layouts.guest>
