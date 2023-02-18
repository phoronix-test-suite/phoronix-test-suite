#!/bin/bash
rm -rf onnxruntime
git clone https://github.com/microsoft/onnxruntime
cd onnxruntime
git checkout v1.14.0
./build.sh --config Release --build_shared_lib --parallel --skip_tests --enable_lto --cmake_extra_defines onnxruntime_BUILD_FOR_NATIVE_MACHINE=ON
echo $? > ~/install-exit-status
cd ~
tar -xf yolov4.tar.gz
tar -xf fcn-resnet101-11.tar.gz
tar -xf super-resolution-10.tar.gz
tar -xf bertsquad-12.tar.gz
tar -xf gpt2-10.tar.gz
tar -xf arcfaceresnet100-8.tar.gz
tar -xf resnet50-v1-12-int8.tar.gz
tar -xf caffenet-12-int8.tar.gz
tar -xf FasterRCNN-12-int8.tar.gz
echo "#!/bin/bash
./onnxruntime/build/Linux/Release/onnxruntime_perf_test \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > onnx
chmod +x onnx
