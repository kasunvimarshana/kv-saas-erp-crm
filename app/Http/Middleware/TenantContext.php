<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domains\Tenant\Models\Tenant;

class TenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->identifyTenant($request);

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found or invalid tenant context'
            ], 404);
        }

        if (!$tenant->isActive()) {
            return response()->json([
                'message' => 'Tenant is not active or has expired'
            ], 403);
        }

        // Set tenant in request for use in controllers
        $request->merge(['tenant' => $tenant]);
        $request->attributes->set('tenant', $tenant);
        
        // Set global tenant context for query scoping
        app()->instance('tenant', $tenant);

        return $next($request);
    }

    /**
     * Identify tenant from request.
     */
    protected function identifyTenant(Request $request): ?Tenant
    {
        // Priority 1: Check X-Tenant-ID header
        if ($request->hasHeader('X-Tenant-ID')) {
            $tenant = Tenant::find($request->header('X-Tenant-ID'));
            if ($tenant) {
                return $tenant;
            }
        }

        // Priority 2: Check X-Tenant-Subdomain header
        if ($request->hasHeader('X-Tenant-Subdomain')) {
            $tenant = Tenant::where('subdomain', $request->header('X-Tenant-Subdomain'))
                ->active()
                ->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Priority 3: Check subdomain from host
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            $tenant = Tenant::where('subdomain', $subdomain)
                ->active()
                ->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Priority 4: Check custom domain
        $tenant = Tenant::where('domain', $host)
            ->active()
            ->first();
        
        return $tenant;
    }
}
