<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9fcdfdbadc3e41e78f53876c1487661e
{
    public static $classMap = array (
        'Press_Sync\\API' => __DIR__ . '/../..' . '/includes/class-api.php',
        'Press_Sync\\CLI' => __DIR__ . '/../..' . '/includes/class-cli.php',
        'Press_Sync\\Dashboard' => __DIR__ . '/../..' . '/includes/class-dashboard.php',
        'Press_Sync\\Press_Sync' => __DIR__ . '/../..' . '/includes/class-press-sync.php',
        'Press_Sync\\Progress' => __DIR__ . '/../..' . '/includes/class-progress.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit9fcdfdbadc3e41e78f53876c1487661e::$classMap;

        }, null, ClassLoader::class);
    }
}
