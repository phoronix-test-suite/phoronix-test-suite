<?php
$log_file_name=getenv("LOG_FILE");
$log_file = file_get_contents($log_file_name);
$test_target = getenv("PTS_TEST_ARGUMENTS");
list($test,$value,$scale,$unit,$proportion)=split(",",$test_target);
list($str1,$str2)=split("\n",$log_file);
$str2=substr($str2,strpos($str1,stripslashes($value)));
list($result)=split(" ",$str2);
$path=dirname($log_file_name);
file_put_contents($path.'/pts-results-scale',$unit);
file_put_contents($path.'/pts-results-proportion',$proportion);
echo $result*$scale;
?>