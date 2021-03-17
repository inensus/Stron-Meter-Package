<?php

namespace Inensus\StronMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\StronMeter\Helpers\ApiHelpers;
use Inensus\StronMeter\Services\MenuItemService;
use Inensus\StronMeter\Services\StronCredentialService;

class InstallPackage extends Command
{
    protected $signature = 'stron-meter:install';
    protected $description = 'Install StronMeter Package';

    private $menuItemService;
    private $apiHelpers;
    private $credentialService;
    public function __construct(
        MenuItemService $menuItemService,
        ApiHelpers $apiHelpers,
        StronCredentialService $credentialService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->apiHelpers = $apiHelpers;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Installing Stron Meter Integration Package\n');
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\StronMeter\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\StronMeter\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);
        $this->apiHelpers->registerStronMeterManufacturer();
        $this->credentialService->createCredentials();

        $this->call('plugin:add', [
            'name' => "StronMeter",
            'composer_name' => "inensus/stron-meter",
            'description' => "Stron Meter integration package for MicroPowerManager",
        ]);
        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package installed successfully..');
    }
}
