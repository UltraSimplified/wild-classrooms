<x-mail::message>
# {{ $heading }}

**Registered by:** {{ $registeredBy->name }} ({{ $registeredBy->email }})

**Group:** {{ $group->title }}

**Address:**<br>
@foreach (array_filter([$group->address_line_1, $group->address_line_2, $group->city, $group->county, $group->postal_code]) as $line)
{{ $line }}<br>
@endforeach

**Public?** {{ $group->public ? 'Yes' : 'No' }}

**Date registered:** {{ $group->created_at?->format('j M Y') }}

<x-mail::button :url="\App\Support\NovaUrl::edit('groups', $group->id)">
Edit group and update map location
</x-mail::button>
</x-mail::message>
