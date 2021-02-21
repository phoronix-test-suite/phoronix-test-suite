#!/bin/bash

tar -xf openvino-github-2021.tar.xz
tar -xf open_model_zoo-20201007.tar.xz
mkdir models

cd openvino-github-2021
mkdir build
cd build
mkdir py
cd py
ln -s /usr/bin/python3 python
cd ..
cmake -DCMAKE_BUILD_TYPE=Release ..
PATH=py:$PATH make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/open_model_zoo/tools/downloader/
pip3 install --user -r requirements.in
python3 downloader.py --name face-detection-0106 -o $HOME/models
python3 downloader.py --name age-gender-recognition-retail-0013 -o $HOME/models
python3 downloader.py --name person-detection-0106 -o $HOME/models

cd ~


BINDIR=intel64
if [ $OS_ARCH = "aarch64" ]
then
	BINDIR=aarch64
fi
echo "#!/bin/bash
./openvino-github-2021/bin/$BINDIR/Release/benchmark_app \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > openvino
chmod +x openvino
