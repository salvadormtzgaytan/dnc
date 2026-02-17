<?php
namespace App\Http\Middleware;

use Closure;
use App\Utils\GeoLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $ip = $request->ip();
            $url = $request->fullUrl();
            $userAgent = $request->userAgent();

            $locationService = new GeoLocation();
            $location = $locationService->getLocation($ip);

            DB::table('user_activity_logs')->insert([
                'user_id' => $user->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'url_visited' => $url,
                'visited_at' => now(),
                'country' => $location['country'],
                'city' => $location['city'],
            ]);

            $user->last_login_at = now();
            $user->last_login_ip = $ip;
            $user->saveQuietly();
        }

        return $response;
    }
}
