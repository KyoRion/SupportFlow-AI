<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\ToolInvoked;

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
        Event::listen(
            InvokingTool::class,
            function (InvokingTool $event): void {
                Log::info('ai.tool.started', [
                    'invocation_id' => $event->invocationId,
                    'tool_invocation_id' => $event->toolInvocationId,
                    'tool' => class_basename($event->tool),
                ]);
            }
        );

        Event::listen(
            ToolInvoked::class,
            function (ToolInvoked $event): void {
                Log::info('ai.tool.completed', [
                    'invocation_id' => $event->invocationId,
                    'tool_invocation_id' => $event->toolInvocationId,
                    'tool' => class_basename($event->tool),
                ]);
            }
        );
    }
}
