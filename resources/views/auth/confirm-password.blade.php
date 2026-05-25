<x-layouts.guest>
    <div style="margin-bottom:20px; font-size:13px; color:var(--ink-5); line-height:1.6;">
        This is a secure area. Please confirm your password before continuing.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div style="margin-bottom:20px;">
            <x-ui.input type="password" name="password" label="Password"
                :error="$errors->first('password')"
                required autofocus autocomplete="current-password" />
        </div>

        <x-ui.button type="submit" variant="primary"
            style="width:100%; justify-content:center;">
            Confirm
        </x-ui.button>
    </form>
</x-layouts.guest>
