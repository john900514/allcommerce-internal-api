<?php

namespace App\Http\Middleware;

use Sentry;
use Closure;
use Throwable;
use Spatie\Activitylog\Models\Activity;

class UserActionToActivityLog
{
    protected $activity_log_model;

    public function __construct(Activity $log)
    {
        $this->activity_log_model = $log;
    }

    public function handle($request, Closure $next)
    {
        try
        {
            $route = $request->route()->uri();
            $ip = $request->ip();

            $payload = [
                'route' => $route,
                'ip' => $ip,
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ];
            // If user is a guest, log guest activity
            if (backpack_auth()->guest()) {
                activity('guest-activity')
                    ->withProperties($payload)
                    ->log('Guest Visiting - '.$route);
            }
            else
            {
                //If user is a user, log user activity
                $user = backpack_user();

                $payload['user'] = $user->name;

                activity('user-activity')
                    ->causedBy($user)
                    ->withProperties($payload)
                    ->log($user->name.' is visiting - '.$route);
            }
        }
        catch(\Throwable $e)
        {
            if(env('APP_ENV') != 'local')
            {
                Sentry\captureException($e);
            }
        }

        return $next($request);
    }
}
