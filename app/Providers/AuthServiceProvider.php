<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Mail\CustomResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
        \Spatie\Activitylog\Models\Activity::class => \App\Policies\ActivityPolicy::class,
        \App\Settings\SeoSettings::class => \App\Policies\SeoSettingsPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
        // ResetPassword::toMailUsing(function (User $notifiable, string $token) {
        //     $url = url(route('password.reset', [
        //         'token' => $token,
        //         'email' => $notifiable->getEmailForPasswordReset(),
        //     ]));

        //     Mail::to($notifiable->email)->send(new CustomResetPassword($url, $notifiable));

        //     // Retorna un MailMessage vacío para evitar errores
        //     return (new MailMessage);
        // });
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new MailMessage)
                ->subject('Restablecer contraseña - ' . config('app.name'))
                ->view('emails.reset-password', [
                    'url' => url(route('password.reset', [
                        'token' => $token,
                        'email' => $notifiable->getEmailForPasswordReset(),
                    ])),
                    'user' => $notifiable,
                ]);
        });
    }
}
