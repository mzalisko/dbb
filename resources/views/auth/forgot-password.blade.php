<x-layouts.guest>
    <div style="margin-bottom:20px; font-size:13px; color:var(--ink-5);">
        Forgot your password? Enter your email address and we will send you a reset link.
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" style="margin-bottom:16px;">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <x-ui.input type="email" name="email" label="Email" :value="old('email')" :error="$errors->first('email')" required autofocus autocomplete="email" />
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Send Reset Link
        </x-ui.button>

        <div style="margin-top:16px; text-align:center; font-size:13px;">
            <a href="{{ route('login') }}" style="color:var(--accent);">Back to login</a>
        </div>
    </form>
</x-layouts.guest>
