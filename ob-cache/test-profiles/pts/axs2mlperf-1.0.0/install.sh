#!/bin/sh
if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
docker build --no-cache -t axs:benchmarks -f Axs_Dockerfile_mlperf_20230505 .
echo $? > ~/install-exit-status
echo "#!/bin/bash
QPS_TARGET=\`cat avg-qps-target\`
echo \"QPS TARGET IS: \$QPS_TARGET\"
docker run axs:benchmarks -c \"time axs byquery loadgen_output,classified_imagenet,framework=onnx,loadgen_mode=PerformanceOnly,loadgen_scenario=Offline,loadgen_dataset_size=500,loadgen_buffer_size=1024,verbosity=1,loadgen_target_qps=\$QPS_TARGET , get performance && cat \\\`axs byquery loadgen_output,classified_imagenet,framework=onnx,loadgen_mode=PerformanceOnly,loadgen_scenario=Offline , get_path\\\`/mlperf_log_summary.txt\" > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > axs2mlperf
chmod +x axs2mlperf
