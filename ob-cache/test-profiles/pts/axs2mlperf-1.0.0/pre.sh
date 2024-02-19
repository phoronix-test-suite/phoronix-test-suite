#!/bin/bash
docker run --rm axs:benchmarks -c "time axs byquery loadgen_output,classified_imagenet,framework=onnx,loadgen_dataset_size=20,loadgen_mode=PerformanceOnly,loadgen_dataset_size=500,loadgen_buffer_size=1024,loadgen_scenario=Offline,loadgen_target_qps=1 , get performance" > eval-out.txt 2>&1 &
EVAL_PID=$!
sleep 120
kill -9 $EVAL_PID
sleep 3
cat eval-out.txt | grep "p\[batch of 1\] inference=" | cut -c 25- | awk '{print $1}' > q-times.txt
count=0;
total=0;
for i in $( awk '{ print $1; }' q-times.txt )
   do 
     total=$(echo $total+$i | bc )
     ((count++))
   done
AVG_INF_TIME=`echo "scale=2; $total / $count" | bc`
echo "AVERAGE TIME IS $AVG_INF_TIME ms"
AVG_QPS=`echo "scale=0; 1000 / $AVG_INF_TIME" | bc`
echo "AVERAGE QPS IS $AVG_QPS qps"
echo $AVG_QPS > avg-qps-target
