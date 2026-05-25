<x-layouts.guest>
    @if (session('status'))
        <x-ui.alert variant="success" style="margin-bottom:16px;">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <x-ui.input type="email" name="email" label="Email" :value="old('email')" :error="$errors->first('email')" required autofocus autocomplete="email" />
        </div>

        <div style="margin-bottom:20px;">
            <x-ui.input type="password" name="password" label="Password" :error="$errors->first('password')" required autocomplete="current-password" />
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--ink-7);">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                Remember me
            </label>
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Log in
        </x-ui.button>

        <div style="margin-top:16px; text-align:center; font-size:13px;">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="color:var(--accent);">Forgot password?</a>
            @endif
        </div>
    </form>
</x-layouts.guest>
