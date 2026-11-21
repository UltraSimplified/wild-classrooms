<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Nova\Dashboards\Main;
use App\Nova\Metrics\GroupTypes;
use App\Nova\Metrics\RegistrationsPerDay;
use App\Nova\Metrics\SpottingsPerDay;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Tool;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Who counts as staff: the people Nova lets in.
     *
     * The old provider hardcoded two staff email addresses in the source. With
     * the repository going public those belong in configuration, not in code.
     *
     * Nova itself admits anyone signed in when running locally, whatever this
     * gate says. The local case is repeated here so that the gate means the
     * same thing as Nova does: policies defer to it, and without it the Users
     * screen would vanish in local development while every other screen
     * stayed.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user): bool {
            return app()->environment('local')
                || in_array($user->email, config('nova.admin_emails', []), true);
        });
    }

    /** @return array<int, Card> */
    protected function cards(): array
    {
        return [
            new SpottingsPerDay,
            new RegistrationsPerDay,
            new GroupTypes,
        ];
    }

    /** @return array<int, Dashboard> */
    protected function dashboards(): array
    {
        return [
            Main::make(),
        ];
    }

    /** @return array<int, Tool> */
    public function tools(): array
    {
        return [];
    }
}
