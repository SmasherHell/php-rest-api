<?php

/**
 * Auto Load Class files by namespace
 *
 * @eg 
 	'namespace' => '/path/to/dir'
 */

$autoload = [
	'Events' => $dir . '/library/Events/',
	'Micro' => $dir . '/library/Micro/',
	'Utilities' => $dir . '/library/Utilities/',
	'Security' => $dir . '/library/Security/',
	'Application' => $dir . '/library/Application/',
	'Interfaces' => $dir . '/library/Interfaces/',
	'Controllers' => $dir . '/controllers/',
	'Models' => $dir . '/models/'
];

return $autoload;
