<?php

namespace App\Providers;

use App\Support\Security\Health as SecurityHealth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Best practice: avoid logging noisy health checks on every HTTP request.
        // Only log these details in console commands or when APP_DEBUG=true.
        if ($this->app->runningInConsole() || (bool) config('app.debug')) {
            $this->logSecurityFeaturesEnabled();
        }

        // Force APP_URL for asset() and Storage::url()
        if (!empty(config('app.url'))) {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
            
            if (str_starts_with(config('app.url'), 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }
    }
    
    /**
     * Log that security features have been enabled and their health status
     * - Green (\e[32m) when OK
     * - Red   (\e[31m) when Failure
     *
     * @return void
     */
    private function logSecurityFeaturesEnabled(): void
    {
        $checks = SecurityHealth::checks();

        // Aggregate overall status
        $allOk = collect($checks)->every(fn ($r) => $r['ok'] === true);

        // Structured log to laravel.log (no ANSI colors)
        // DEBUG level to avoid bloating production logs.
        Log::debug('🔒 Security features enabled', [
            'overall_ok' => $allOk,
            // checks can be large; include only counts in logs
            'checks_count' => is_array($checks) ? count($checks) : null,
        ]);

        // Colorized console output only when running in console
        if ($this->app->runningInConsole()) {
            $GREEN = "\e[32m"; // green
            $RED   = "\e[31m"; // red
            $RESET = "\e[0m";  // reset

            $prefix = $allOk ? "{$GREEN}[SECURITY]{$RESET}" : "{$RED}[SECURITY]{$RESET}";
            error_log($prefix . ' Boot checks: ' . ($allOk ? ($GREEN . 'OK' . $RESET) : ($RED . 'FAIL' . $RESET)));

            foreach ($checks as $name => $result) {
                $color = $result['ok'] ? $GREEN : $RED;
                $status = $result['ok'] ? 'OK' : 'FAIL';
                error_log(sprintf('%s %s: %s - %s%s', $prefix, $name, $status, $color, $result['message'] . $RESET));
            }

            if (!$allOk) {
                error_log($RED . '[SECURITY] One or more security checks failed. Review configuration before proceeding to production.' . $RESET);
            }
        }
    }
}
