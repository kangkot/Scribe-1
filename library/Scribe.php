<?php

class Scribe {
	public static $path;
	public static $url;
	public static $config;

	public static function autoload($class) {
		$file = str_replace('_', DIRECTORY_SEPARATOR, $class);
		if (file_exists(self::$path . '/library/' . $file . '.php')) {
			include(self::$path . '/library/' . $file . '.php');
		}
	}

	public static function exception($e) {
		header('HTTP/1.1 500 Internal Server Error');
?><!DOCTYPE html>
<html>
<head>
	<link href="<?php echo Scribe::$url ?>/static/style.css" rel="stylesheet" />
	<title>Failed loading Scribe!</title>
</head>
<body>
	<div id="page" class="error">
		<h1>Whoops!</h1>
		<p>An error (<code><?php echo get_class($e) ?></code>) occurred while loading Scribe:</p>
		<pre><?php echo $e->getMessage() ?></pre>
		<p>This should never happen!</p>
		<p>Developer traceback</p>
		<pre><?php echo self::trace_to_text($e->getTrace()) ?></pre>
	</div>
</body>
</html>
<?php
	}

	protected static function trace_to_text($traced) {
		$return = '';
		foreach ($traced as $num => $trace) {
			$func = $trace['function'];
			if (isset($trace['class'])) {
				$func = $trace['class'] . $trace['type'] . $trace['function'];
			}
			if (!isset($trace['file'])) {
				$trace['file'] = '[unknown]';
			}
			if (!isset($trace['line'])) {
				$trace['line'] = 0;
			}
			$args = array();
			foreach($trace['args'] as $arg) {
				if (is_object($arg)) {
					$args[] = get_class($arg);
					continue;
				}
				if (is_array($arg)) {
					$args[] = 'array';
					continue;
				}
			}
			$args = implode(', ', $args);
			$return .= sprintf("  #%d - %s @ L%d: %s(%s)\n", $num, str_replace(self::$path, 'Scribe', $trace['file']), $trace['line'], $func, $args);
		}
		return $return;
	}

	public static function bootstrap() {
		Scribe::$path = dirname(dirname(__FILE__));
		require_once(Scribe::$path . '/config.php');

		Scribe_Router::route();
	}
}

spl_autoload_register(array('Scribe', 'autoload'));
set_exception_handler(array('Scribe', 'exception'));