<?php

namespace CodeeshopOc\DocumentPHP;

include_once '/opt/lampp/htdocs/myshop1/system/engine/controller.php';

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

class FetchFiles {
	public $data = [];
	public $destination_path = '/opt/lampp/htdocs/myshop1/catalog/controller/api/';

	function rrmdir($dir) {
		// return;
		if (is_dir($dir)) {
			$objects = scandir($dir);

			foreach ($objects as $object) {
				if ($object != '.' && $object != '..') {
					if (filetype($dir . '/' . $object) == 'dir') {$this->rrmdir($dir . '/' . $object);} else {unlink($dir . '/' . $object);}
				}
			}

			reset($objects);
			// rmdir($dir);
		}
	}

	function recursive_copy($src, $dst) {
		if (is_dir($src)) {
			$dir = opendir($src);

			while (($file = readdir($dir))) {
				if ((substr($file, -1) != '_') && ($file != '.') && ($file != '..')) {
					if (is_dir($src . '/' . $file)) {
						$this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
					} else {
						if (is_file($this->destination_path . $file)) {
							$this->start_processing($this->destination_path . $file);
							// print_r($this->destination_path . $file);
						}
						// die;
						// copy($src . '/' . $file, $dst . '/' . $file);
					}
				}
			}
			closedir($dir);

			return;
		} elseif (is_file($src)) {
			$dir = substr($dst, 0, strrpos($dst, '/'));
			// mkdir($dir, 0777, true);

			print_r($src);die;
			// copy($src, $dst);
		} else {
			die('<strong>Not found : </strong> ' . $src);
			// print_r($src);die;
		}
	}

	function get_function($method, $class = null) {

		if (!empty($class)) {
			$func = new ReflectionMethod($class, $method);
		} else {
			$func = new ReflectionFunction($method);
		}

		$f = $func->getFileName();
		$start_line = $func->getStartLine() - 1;
		$end_line = $func->getEndLine();
		$length = $end_line - $start_line;

		$source = file($f);
		$source = implode('', array_slice($source, 0, count($source)));
		$source = preg_split("/" . PHP_EOL . "/", $source);

		$body = '';
		for ($i = $start_line; $i < $end_line; $i++) {
			$body .= "{$source[$i]}\n";
		}

		return $body;
	}

	function start_processing($file) {
		include_once $file;

		$start = strpos($file, 'controller') + strlen('controller');
		$end = strpos($file, '.php') - $start;
		$route = substr($file, $start, $end);
		$class_name = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
		$myclass = new $class_name([]);
		$class_methods = get_class_methods($myclass);
		$class_vars = get_class_vars(get_class($myclass));

		$current_data = [];

		// echo "<pre>";
		foreach ($class_methods as $method_name) {
			$function_string = $this->get_function($method_name, $myclass);

			if ($this->isReturningJSONResponse($function_string)) {
				$method_types_vars = $this->findMethodTypesVars($function_string);
				$current_method_type = empty($method_types_vars['POST']) ? 'GET' : 'POST';

				$current_data[] = [
					'full_route' => $route . ($method_name == 'index' ? '' : ('/' . $method_name)),
					'current_method_type' => $current_method_type,
					'route' => $route,
					'method_name' => $method_name,
					'method_types_vars' => $method_types_vars,
					'file' => $file,
				];
			}
		}

		$this->data[$class_name] = $current_data;
		// print_r($this->data);
		// echo "</pre>";
	}

	function findMethodTypesVars($function_string = '') {
		$method_types = [];
		$method_types['POST'] = $this->getParams($function_string, "/->post\[/i", ['->post[', '\''], '');
		$method_types['GET'] = $this->getParams($function_string, "/->get\[/i", ['->get[', '\''], '');
		return $method_types;
	}

	function isReturningJSONResponse($function_string = '') {
		preg_match_all("/setOutput\(/i", $function_string, $matches);

		return !empty($matches[0]) ? true : false;
	}
	function getParams($function_string, $pattern, $find, $replace = '') {
		preg_match_all($pattern, $function_string, $matches, PREG_OFFSET_CAPTURE);

		$var_names = [];
		foreach ($matches[0] as $key => $value) {
			$strpos = strpos(substr($function_string, $value[1], strlen($function_string)), ']');
			$var_name = str_replace($find, $replace, substr($function_string, $value[1], $strpos));

			$var_names[] = $var_name;
		}

		return array_unique($var_names);
	}
}

?>