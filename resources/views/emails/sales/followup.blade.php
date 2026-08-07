<x-emails::layout
    :preview="$headline"
    :title="$headline"
    accent="indigo"
>
    <x-emails::heading kicker="BarangayBooks sales follow-up" accent="indigo">
        {{ $headline }}
    </x-emails::heading>

    <x-emails::paragraph>
        {{ $body }}
    </x-emails::paragraph>

    <x-emails::button :href="$ctaUrl" color="indigo">
        {{ $ctaLabel }}
    </x-emails::button>

    <x-emails::paragraph muted>
        If you'd rather we stopped following up, just reply to this email and we'll remove you from the sequence.
    </x-emails::paragraph>
</x-emails::layout>
