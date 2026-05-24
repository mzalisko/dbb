<x-layouts.guest>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <label class="label" for="name">Name</label>
            <x-ui.input type="text" id="name" name="name" :value="old('name')" required autofocus />
            @error('name')
                <p style="color:var(--bad); font-size:12px; margin-top:6px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:20px;">
            <label class="label" for="email">Email</label>
            <x-ui.input type="email" id="email" name="email" :value="old('email')" required />
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
            <label class="label" for="password_confirmation">Confirm Password</label>
            <x-ui.input type="password" id="password_confirmation" name="password_confirmation" required />
        </div>

        <x-ui.button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
            Register
        </x-ui.button>

        <div style="margin-top:16px; text-align:center; font-size:13px;">
            <a href="{{ route('login') }}" style="color:var(--accent);">Already have an account?</a>
        </div>
    </form>
</x-layouts.guest>
