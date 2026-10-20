<?php

use Deptrac\Deptrac\Contract\Config\Collector\ClassLikeConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $config
        ->paths('./app')
        ->excludeFiles('#.*Test\.php$#')
        ->layers(
            $controllers = Layer::withName('Controllers')->collectors(
                ClassLikeConfig::create('App\\Http\\Controllers\\.*'),
            ),
            $requests = Layer::withName('Requests')->collectors(
                ClassLikeConfig::create('App\\Http\\Requests\\.*'),
            ),
            $services = Layer::withName('Services')->collectors(
                ClassLikeConfig::create('App\\Services\\.*'),
            ),
            $models = Layer::withName('Models')->collectors(
                ClassLikeConfig::create('App\\Models\\.*'),
            ),
        )
        ->rulesets(
            Ruleset::forLayer($controllers)->accesses($requests, $services, $models),
            Ruleset::forLayer($requests),
            Ruleset::forLayer($services)->accesses($models),
            Ruleset::forLayer($models),
        );
};
