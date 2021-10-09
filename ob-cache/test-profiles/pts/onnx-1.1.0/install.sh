#!/bin/bash

rm -rf onnxruntime
git clone https://github.com/microsoft/onnxruntime
cd onnxruntime
git checkout v1.8.2
./build.sh --config Release --build_shared_lib --parallel --use_openmp --skip_tests
echo $? > ~/install-exit-status
retVal=$?
if [ $retVal -ne 0 ]; then
    echo $retVal > ~/install-exit-status
    exit $retVal
fi

cd ~
tar -xf yolov4.tar.gz
tar -xf fcn-resnet101-11.tar.gz
tar -xf shufflenet-v2-10.tar.gz
tar -xf super-resolution-10.tar.gz
tar -xf bertsquad-10.tar.gz

echo "#!/bin/bash

OMP_NUM_THREADS=\$NUM_CPU_CORES ./onnxruntime/build/Linux/Release/onnxruntime_perf_test \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > onnx
chmod +x onnx
