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
                'app' => 'oauth api',
                'app_url' => env('APP_URL'),
                'route' => $route,
                'ip' => $ip,
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ];

            if(array_key_exists('password', $payload['data']))
            {
                unset($payload['data']['password']);
            }
            // If user is a guest, log guest activity
            if (auth()->guest()) {
                activity('guest-activity')
                    ->withProperties($payload)
                    ->log('Guest Visiting - '.$route);
            }
            else
            {
                //If user is a user, log user activity
                $user = auth()->user();

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
            else
            {
                Log::info($e->getMessage());
            }
        }

        return $next($request);
    }
}
