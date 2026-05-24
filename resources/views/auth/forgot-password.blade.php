<x-layouts.guest>
    <div style="margin-bottom:20px; font-size:13px; color:var(--ink-5);">
        Forgot your password? Enter your email address and we will send you a reset link.
    </div>

    @if (session('status'))
        <div style="margin-bottom:16px; padding:12px; background:var(--ok-soft); color:var(--ok); border-radius:4px; font-size:13px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <label class="label" for="email">Email</label>
            <x-ui.input type="email" id="email" name="email" :value="old('email')" required autofocus />
            @error('email')
                <p style="color:var(--bad); font-size:12px; margin-top:6px;">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Send Reset Link
        </x-ui.button>

        <div style="margin-top:16px; text-align:center; font-size:13px;">
            <a href="{{ route('login') }}" style="color:var(--accent);">Back to login</a>
        </div>
    </form>
</x-layouts.guest>
