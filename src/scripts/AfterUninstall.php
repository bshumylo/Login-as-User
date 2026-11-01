<?php

declare(strict_types=1);

use Espo\Core\Container;
use Espo\Core\DataManager;

/**
 * Called when the extension is uninstalled.
 */
class AfterUninstall
{
    public function run(Container $container): void
    {
        $container->getByClass(DataManager::class)->clearCache();
    }
}
