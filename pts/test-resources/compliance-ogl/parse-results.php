<?php

function extract_from_glew_output($source, $item)
{
	$source_lines = explode("\n", $source);

	foreach($source_lines as $line)
	{
		$line_components = explode(":", $line);

		if(count($line_components) == 2)
		{
			if($line_components[0] == $item)
				return trim($line_components[1]);
		}
	}

}
function extension_present($source, $item)
{
	$value = extract_from_glew_output($source, $item);

	if($value == "OK")
		return "PASS";
	else
		return "FAIL";
}

$ogl_results = array();
$log_file = file_get_contents(getenv("LOG_FILE"));

array_push($ogl_results, extension_present($log_file, "GL_VERSION_1_1"));
array_push($ogl_results, extension_present($log_file, "GL_VERSION_1_2"));
array_push($ogl_results, extension_present($log_file, "GL_VERSION_1_3"));
array_push($ogl_results, extension_present($log_file, "GL_VERSION_1_4"));
array_push($ogl_results, extension_present($log_file, "GL_VERSION_1_5"));
array_push($ogl_results, extension_present($log_file, "GL_VERSION_2_0"));
array_push($ogl_results, extension_present($log_file, "GL_VERSION_2_1"));

echo implode(",", $ogl_results);

?>
