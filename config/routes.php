<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::plugin(
    'Gls',
    ['path' => '/gls'],
    function (RouteBuilder $routes) {
        $routes->fallbacks(DashedRoute::class);
    }
);
Router::connect('/end/*', ['controller' => 'Gls', 'action' => 'end', 'plugin' => 'Gls']);
Router::connect('/submit/*', ['controller' => 'Gls', 'action' => 'submit', 'plugin' => 'Gls']);
Router::connect('/gls-pl/*', ['controller' => 'Gls', 'action' => 'index', 'plugin' => 'Gls']);
Router::connect('/test/*', ['controller' => 'Gls', 'action' => 'test', 'plugin' => 'Gls']);