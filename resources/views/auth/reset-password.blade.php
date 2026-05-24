<x-layouts.guest>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <div style="margin-bottom:20px;">
            <label class="label" for="email">Email</label>
            <x-ui.input type="email" id="email" name="email" :value="old('email', request()->email)" required autofocus />
            @error('email')
                <p style="color:var(--bad); font-size:12px; margin-top:6px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:20px;">
            <label class="label" for="password">New Password</label>
            <x-ui.input type="password" id="password" name="password" required />
            @error('password')
                <p style="color:var(--bad); font-size:12px; margin-top:6px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:20px;">
            <label class="label" for="password_confirmation">Confirm Password</label>
            <x-ui.input type="password" id="password_confirmation" name="password_confirmation" required />
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Reset Password
        </x-ui.button>
    </form>
</x-layouts.guest>
