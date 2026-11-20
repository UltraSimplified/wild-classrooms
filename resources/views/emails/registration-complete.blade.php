<x-mail::message>
# {{ $heading }}

Thank you for signing up to our “Spot 60 Species in a Year” wildlife challenge.
You now have a personalised woodland to which you can add up to 10 members —
individuals within a family or group, classes within a school, or just
yourself. Once signed in, you can add members under the edit group section.

Each member or group is given a bare tree in your virtual woodland. You can
reach your woodland, or see how other groups are doing, by clicking a tree on
the map on the home page or by searching for a group by name.

Each month you are issued with a challenge to spot five different plants and
animals. When you see one of that month's species you can tick it off and add
photos or drawings. If you spot something interesting that isn't on the list,
add it as a Wild Spotting. Every time you tick off a species, a leaf appears on
your tree — the more species spotted, the greener your tree.

The project can take just five minutes a week, but by the end everyone should
be able to identify at least 60 different species.

<x-mail::button :url="route('groups.woodland', ['group' => $group->id, 'slug' => Str::slug($group->title)])">
Visit your woodland
</x-mail::button>

Your username is **{{ $member->name }}**

Happy spotting!

### Tamsin

Tamsin Bent<br>
Wild Classrooms Project Officer
</x-mail::message>
