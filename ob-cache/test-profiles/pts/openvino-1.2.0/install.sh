#!/bin/bash
mkdir models
rm -rf openvino-github
tar -xf openvino-2022.3.tar.xz
cd openvino-github
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release -DTREAT_WARNING_AS_ERROR=OFF ..
make -j $NUM_CPU_CORES
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ]; then
	echo $EXIT_STATUS > ~/test-exit-status
	exit 2
fi
cd ~/openvino-github/thirdparty/open_model_zoo/tools/model_tools
pip3 install --user -r requirements.in
python3 downloader.py --name face-detection-0206 -o $HOME/models
python3 downloader.py --name age-gender-recognition-retail-0013 -o $HOME/models
python3 downloader.py --name person-detection-0106 -o $HOME/models
python3 downloader.py --name weld-porosity-detection-0001 -o $HOME/models
python3 downloader.py --name vehicle-detection-0202 -o $HOME/models
python3 downloader.py --name person-vehicle-bike-detection-2004 -o $HOME/models
python3 downloader.py --name machine-translation-nar-en-de-0002 -o $HOME/models
echo $? > ~/install-exit-status
cd ~
BINDIR=intel64
if [ $OS_ARCH = "aarch64" ]
then
	BINDIR=aarch64
fi
echo "#!/bin/bash
./openvino-github/bin/$BINDIR/Release/benchmark_app \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > openvino
chmod +x openvino
