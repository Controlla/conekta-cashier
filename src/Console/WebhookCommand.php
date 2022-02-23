<?php

namespace Controlla\ConektaCashier\Console;

use Illuminate\Console\Command;
use Controlla\ConektaCashier\Cashier;

class WebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashier:webhook
            {--S|synchronous : Allows you to decide if the events will be synchronous or asynchronous}
            {--url= : The URL endpoint for the webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the Conekta webhook to interact with Cashier.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $endpoint = new Cashier();
        $endpoint->createWebhook(['url' => $this->option('url') ?? route('cashier.webhook')]);

        $this->info('The Conekta webhook was created successfully. Retrieve the webhook secret in your Conekta dashboard and define it as an environment variable.');
    }
}
