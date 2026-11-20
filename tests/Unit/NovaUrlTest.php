<?php

declare(strict_types=1);

use App\Support\NovaUrl;

it('builds resource edit and detail links from the configured nova path', function (): void {
    config(['nova.path' => '/nova']);

    expect(NovaUrl::edit('groups', 12))->toEndWith('/nova/resources/groups/12/edit')
        ->and(NovaUrl::detail('spottings', 7))->toEndWith('/nova/resources/spottings/7');
});

it('tolerates a nova path given without or with a trailing slash', function (): void {
    config(['nova.path' => 'admin/']);

    expect(NovaUrl::edit('groups', 1))->toEndWith('/admin/resources/groups/1/edit');
});
