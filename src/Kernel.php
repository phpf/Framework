<?php

namespace xpl\Framework;

use xpl\Dependency\DI;
use xpl\Api\Exception as ApiError;
use xpl\Http\Exception as HttpError;
use xpl\Http\Exception\NotFound as HTTP_404;
use xpl\Foundation\ControllerInterface;
use xpl\Routing\Router as Router;
use xpl\Routing\RouteInterface as Route;
use xpl\Bundle\BundleInterface as Bundle;

class Kernel 
{
	
	protected $di;
	protected $request;
	protected $response;
	
	public function __construct(DI $di) {
		$this->di = $di;
	}
	
	/**
	 * Runs the application from start to finish.
	 * 
	 * @param string $appID Application ID.
	 */
	public function __invoke($appID) {
		$app = $this->start($appID);
		$this->run($app);
		$this->send($app);
	}

	/**
	 * Boots and sets the app.
	 * 
	 * @param string $appID Application ID string.
	 */
	public function start($appID) {
			
		$this->request = $this->di->resolve('request');
		$this->response = $this->di->resolve('response');
		
		trigger('start', $this->di, $appID);
		
		// Boot the app
		$this->di['bundles']->boot("app.{$appID}");
		
		// Set the app
		return $this->di['bundles']->getBundle("app.{$appID}");
	}
	
	/**
	 * Routes the request.
	 */
	public function run(Bundle $app) {
		
		trigger('run', $app, $this->di);
		
		// Catch HTTP exceptions in or prior to the "respond" event.
		try {
			// Catch API exceptions in or prior to the controller
			try {
				
				if ($route = $this->route($app)) {
					
					$body = $this->dispatch($app, $route);
					
					$this->response->setBody($body);
				}
				
			} catch (ApiError $e) {
				trigger('api_error', $e, $app, $this->di);
			}
			
			trigger('respond', $app, $this->di);
			
			$app->onRespond($this->response);
		
		} catch (HttpError $e) {
			trigger('http_error', $e, $app, $this->di);
		}
	}
	
	/**
	 * Sends the response.
	 */
	public function send(Bundle $app) {
		
		trigger('send', $app, $this->di);
		
		// Send response headers & body
		$this->response->send();
	}
	
	/**
	 * Routes the request and returns the matched route.
	 * 
	 * @return \xpl\Routing\RouteInterface Matched route.
	 * 
	 * @throws \xpl\Http\Exception\NotFound if no route is matched.
	 */
	protected function route(Bundle $app) {
		
		$collection = $app->getRoutes();
		
		$router = $this->di->resolve('web.router', $collection);
		$kernel = $this->di->resolve('web.kernel', $this->request, $router);
		
		if (! $route = $kernel()) {
			throw new HTTP_404('Page not found.');
		}
		
		trigger('route', $app, $route, $this->request);
		
		$app->onRoute($route, $this->request);
		
		return $route;
	}
	
	/**
	 * Routes the request and invokes the controller.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @return mixed Body content.
	 */
	protected function dispatch(Bundle $app, Route $route) {
		
		$callback = $this->getRouteCallback($route, $app);
		
		trigger('dispatch', $app, $route, $this->request);
		
		$app->onDispatch($route, $this->request);
		
		return call_user_func_array($callback, $route->getParams());
	}
	
	/**
	 * Returns the callback for a route.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @return callable Route callback.
	 */
	protected function getRouteCallback(Route $route, Bundle $app) {
		
		$callback = $route->getAction();
		
		if (is_string($callback) && false !== strpos($callback, '::')) {
			
			list($class, $method) = explode('::', $callback);
			
			$controller = $this->di->resolve($class, $this->request, $this->response, $route, $app);
			
			if ($controller) {
				$callback = array($controller, $method);
			}
		}
		
		return $callback;
	}
	
}
