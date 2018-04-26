<?php
	#
	# JIRA API helper
	#

	$dir = dirname(__FILE__);
	include("$dir/../include/lib_sdl.php");
	header("Content-type: application/json");

	$method = $_GET['method'];

	#
	#
	# The method is required.
	#
	if (empty($method)){
		$results = array(
			'ok' => false,
			'error' => 'method_missing',
		);

	}elseif ($_SERVER['REQUEST_METHOD'] === 'POST'){

		if ($method === 'sdl.generateboard'){

			$inputJSON = file_get_contents('php://input');
			$input = json_decode($inputJSON, TRUE); //convert JSON into array

			foreach (array("user", "project_name", "spec", "list_of_modules") as $key){
				if ($input[$key] == null){
					$results = array(
						'ok'  => false,
						'error' => 'missing parameter: ' . $key,
					);
				}
			}

			$ret = sdl_generate($input);
			$results = $ret;
		}

	}elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

		if ($method === 'sdl.listmodule'){

			$ret = sdl_module_list();
			$results = $ret;

		}

	}
	if (! isset($results)){
		$results = array(
			'ok'	=> false,
			'error' => 'method_unknown',
		);
	}

	echo(json_encode($results, JSON_UNESCAPED_SLASHES));
