<?php

namespace Yadahan\AuthenticationLog\Listeners;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\Login;
use Yadahan\AuthenticationLog\AuthenticationLog;
use Yadahan\AuthenticationLog\Notifications\NewDevice;

class LogSuccessfulLogin
{
    /**
     * The request.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create the event listener.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        if($event->guard != 'wink') {
            $user = $event->user;
            $ip = $this->request->ip();
            $userAgent = $this->request->userAgent();
            $known = $user->authentications()->whereIpAddress($ip)->whereUserAgent($userAgent)->first();

            $authenticationLog = new AuthenticationLog([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'login_at' => Carbon::now(),
            ]);

            $user->authentications()->save($authenticationLog);

            if ( !$known && config('authentication-log.notify')) {
                $user->notify(new NewDevice($authenticationLog));
            }
        }
    }
}
