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

echo "#!/bin/bash
./openvino-github-2021/bin/intel64/Release/benchmark_app \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > openvino
chmod +x openvino
