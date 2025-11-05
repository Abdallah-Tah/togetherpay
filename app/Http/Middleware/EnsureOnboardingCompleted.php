<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if user is not authenticated or already completed onboarding
        if (! $user || $user->hasCompletedOnboarding()) {
            return $next($request);
        }

        // Allow access to onboarding routes, logout, and verification routes
        $allowedRoutes = [
            'onboarding.*',
            'logout',
            'verification.*',
            'password.*',
        ];

        foreach ($allowedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Redirect to onboarding if not completed
        return redirect()->route('onboarding.welcome');
    }
}
