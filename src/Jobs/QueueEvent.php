<?php

namespace MasudZaman\MatomoTracker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MasudZaman\MatomoTracker\LaravelMatomoTracker;

class queueEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $category;
    protected string $action;
    protected $name;
    protected $value;

    public function __construct(string $category, string $action, $name = null, $value = null)
    {
        $this->category = $category;
        $this->action = $action;
        $this->name = $name;
        $this->value = $value;
    }

    public function handle()
    {
        $matomoTracker = new LaravelMatomoTracker();
        // Queue the event using the resolved LaravelMatomoTracker instance
        $matomoTracker->doTrackEvent($this->category, $this->action, $this->name, $this->value);
    }
}
