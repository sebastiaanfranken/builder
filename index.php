<?php

/*
 * Enable displaying of *all* errors
 */
error_reporting(E_ALL);

/*
 * Turn errors on for users. This is a debug setting.
 */
ini_set('display_errors', 'On');

/**
 * Wrapper around PHP's print_r wrapper in HTML pre tags
 *
 * @param mixed $what The PHP thing (object, variable, class) to print
 * @return string
 */
function pr($what)
{
	return '<pre>' . print_r($what, true) . '</pre>';
}

/**
 * Autoloader
 *
 * @param mixed $class The classname to load
 * @return void
 */
spl_autoload_register(function($class) {
	$file = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';

	if(file_exists($file))
	{
		require_once($file);
	}
	else
	{
		trigger_error(sprintf('The requested file <em>%s</em> could not be found.', $file));
	}
});

/*
 * Create a new Builder instance and load the JSON file (hyve) into
 * it ...
 */
$log =  file_get_contents('example.json');
$builder = (new Sfranken\Builder($log))->getCollection('log');

/*
 * ... Print the instance as HTML
 */
print $builder->build();
