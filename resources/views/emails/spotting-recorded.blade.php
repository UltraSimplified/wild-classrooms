<x-mail::message>
# {{ $heading }}

**Uploaded by:** {{ $uploadedBy->name }} ({{ $uploadedBy->email }})

**Group:** {{ $spotter }}

**Challenge:** {{ $challenge->title }}

**Location:** {{ $spotting->location }}

@if ($what3words !== '')
**What3Words:** [{{ $what3words }}](https://w3w.co/{{ $what3words }})
@endif

**Notes:**<br>
{{ $spotting->notes }}

**Date uploaded:** {{ $spotting->created_at?->format('j M Y') }}

<x-mail::button :url="\App\Support\NovaUrl::edit('spottings', $spotting->id)">
Review &amp; edit spottings
</x-mail::button>
</x-mail::message>
