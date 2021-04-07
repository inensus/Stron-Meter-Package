<?php


namespace Inensus\StronMeter\Console\Commands;


use Illuminate\Console\Command;
use Inensus\StronMeter\Services\MenuItemService;
use Inensus\StronMeter\Services\StronCredentialService;

class UpdatePackage extends Command
{
    protected $signature = 'stron-meter:update';
    protected $description = 'Update StronMeter Package';

    private $menuItemService;
    private $credentialService;
    public function __construct(
        MenuItemService $menuItemService,
        StronCredentialService $credentialService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Stron Meter Integration Updating Started\n');
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/stron-meter');
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/stron-meter');


        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\StronMeter\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Updating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\StronMeter\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);

        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package updated successfully..');
    }
}
