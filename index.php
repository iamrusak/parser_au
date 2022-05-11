<?php

require_once('vendor/autoload.php');
require_once('functions/requests_au.php');

function start_parsing($argv)
{
	get_response($argv[1]);

	if (file_exists('result.json')) {
		var_dump(json_decode(file_get_contents('result.json')));
	};
}

start_parsing($argv);