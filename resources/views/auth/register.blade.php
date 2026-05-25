<x-layouts.guest>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <x-ui.input type="text" name="name" label="Name" :value="old('name')" :error="$errors->first('name')" required autofocus autocomplete="name" />
        </div>

        <div style="margin-bottom:20px;">
            <x-ui.input type="email" name="email" label="Email" :value="old('email')" :error="$errors->first('email')" required autocomplete="email" />
        </div>

        <div style="margin-bottom:20px;">
            <x-ui.input type="password" name="password" label="Password" :error="$errors->first('password')" required autocomplete="new-password" />
        </div>

        <div style="margin-bottom:20px;">
            <x-ui.input type="password" name="password_confirmation" label="Confirm Password" required autocomplete="new-password" />
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Register
        </x-ui.button>

        <div style="margin-top:16px; text-align:center; font-size:13px;">
            <a href="{{ route('login') }}" style="color:var(--accent);">Already have an account?</a>
        </div>
    </form>
</x-layouts.guest>
