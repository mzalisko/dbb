<x-layouts.guest>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <label class="label" for="email">Email</label>
            <x-ui.input type="email" id="email" name="email" :value="old('email')" required autofocus />
            @error('email')
                <p style="color:var(--bad); font-size:12px; margin-top:6px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:20px;">
            <label class="label" for="password">Password</label>
            <x-ui.input type="password" id="password" name="password" required />
            @error('password')
                <p style="color:var(--bad); font-size:12px; margin-top:6px;">{{ $message }}</p>
            @enderror
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
