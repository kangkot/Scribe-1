<?php

class Scribe_Router {
	public static function route($custom = array()) {
		$routes = array(
			'/' => 'Scribe_Controller_Page.view',
			'/:page' => 'Scribe_Controller_Page.view',
			'/:controller' => 'Scribe_Controller_{controller}',
			'/:controller/:method' => 'Scribe_Controller_{controller}.{method}'
		);

		if (!empty($custom)) {
			$routes = array_merge($routes, $custom);
		}

		header('Content-Type: text/html; charset=utf-8');

		$url = parse_url($_SERVER['REQUEST_URI']);
		$path = str_replace(Scribe::$url, '', $url['path']);

		// If the path ends with a trailing slash, call the "index"
		// method on it.
		if ($path[strlen($path) - 1] === '/') {
			$action = 'index';
		}
		if ($path === '/') {
			$controller = $routes['/'];
			if (strpos($controller, '.') !== false) {
				list($controller, $action) = explode('.', $controller, 2);
			}
			
			self::call($controller, $action, $url);
			return;
		}
		$path = trim($path, '/');

		// We no longer need this.
		unset($routes['/']);

		$parts = explode('/', $path);
		foreach ($routes as $route_url => $controller) {
			$vars = array();
			$route_url = ltrim($route_url, '/');
			$route_parts = explode('/', $route_url);

			if (count($route_parts) != count($parts)) {
				continue;
			}

			for ($i = 0; $i < count($route_parts); $i++) {
				$real = $parts[$i];
				$wanted = $route_parts[$i];

				if (strpos($wanted, ':') === 0) {
					$wanted = substr($wanted, 1);
					$vars[$wanted] = urldecode($real);
				}
				else {
					if ($real !== $wanted) {
						continue 2;
					}
				}
			}

			foreach ($vars as $name => $value) {
				if ($name === 'controller')
					$value = ucfirst($value);

				if (strpos($controller, '{' . $name . '}') !== false) {
					$controller = str_replace('{' . $name . '}', $value, $controller);
					unset($vars[$name]);
				}
			}

			if (strpos($controller, '.') !== false) {
				list($controller, $action) = explode('.', $controller, 2);
			}

			$vars = array_merge($_GET, $vars);
			return self::call($controller, $action, $vars);
		}

		throw new Exception('No route found -- are you sure this is a valid URL?');
	}

	protected static function call($controller, $action, $args) {
		if (!class_exists($controller))
			throw new Exception('Could not find ' . $controller . ' with method ' . $action);

		$callback = array($controller, $action);
		$args = self::_sortArgs($callback, $args);

		call_user_func_array($callback, $args);
	}

	/**
	 * Sort parameters by order specified in method declaration
	 *
	 * Takes a callback and a list of available params, then filters and sorts
	 * by the parameters the method actually needs, using the reflection APIs
	 *
	 * @author Morten Fangel <fangel@sevengoslings.net>
	 * @param callback $callback
	 * @param array $params
	 * @return array
	 */
	protected static function _sortArgs($callback, $params) {
		// Takes a callback and a list or params and filter and
		// sort the list by the parameters the method actually needs
		
		if( is_array($callback) ) {
			$ref_func = new ReflectionMethod($callback[0], $callback[1]);
		} else {
			$ref_func = new ReflectionFunction($callback);
		}
		// Create a reflection on the method
		
		$ref_parameters = $ref_func->getParameters();
		// finds the parameters needed for the function via Reflections
		
		$ordered_parameters = array();
		foreach($ref_parameters AS $ref_parameter) {
			// Run through all the parameters we need
			
			if( isset($params[$ref_parameter->getName()]) ) {
				// We have this parameters in the list to choose from
				$ordered_parameters[] = $params[$ref_parameter->getName()];
			} elseif( $ref_parameter->isDefaultValueAvailable() ) {
				// We don't have this parameter, but it's optional
				$ordered_parameters[] = $ref_parameter->getDefaultValue();
			} else {
				// We don't have this parameter and it wasn't optional, abort!
				throw new Exception('Missing parameter ' . $ref_parameter->getName() . '');
				$ordered_parameters[] = null;
			}
		}
		return $ordered_parameters;
	}

	public static function redirect($url, $permanent = false) {
		$code = ($permanent ? 301 : 302);
		header('Location: ' . Scribe::$url . $url, true, $code);
		die();
	}
}