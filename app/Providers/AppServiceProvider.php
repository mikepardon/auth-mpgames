<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \Laravel\Passport\Contracts\AuthorizationViewResponse::class,
            fn () => new \Laravel\Passport\Http\Responses\SimpleViewResponse('vendor.passport.authorize')
        );
    }

    public function boot(): void
    {
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addDays(15));

        $this->app['events']->listen(SocialiteWasCalled::class, AppleExtendSocialite::class);
    }
}
