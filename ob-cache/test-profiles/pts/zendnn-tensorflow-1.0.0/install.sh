#!/bin/sh
unzip -o TF_v2.10_ZenDNN_v4.0_Python_v3.10.zip
cd TF_v2.10_ZenDNN_v4.0_Python_v3.10
sed -i 's/sudo / /g' scripts/gather_hw_os_kernel_bios_info.sh #Avoid prompting for sudo for extra info...
sed -i 's/python -m pip install/pip3 install --user/g' scripts/TF_ZenDNN_setup_release.sh
source scripts/TF_ZenDNN_setup_release.sh
cd ~
unzip -o tf_resnetv1_50_imagenet_224_224_6.97G_1.1_Z4.0.zip
unzip -o tf_inceptionv4_imagenet_299_299_24.55G_1.1_Z4.0.zip
unzip -o tf_mobilenetv1_1.0_imagenet_224_224_1.14G_1.1_Z4.0.zip
echo "#!/bin/bash
cd TF_v2.10_ZenDNN_v4.0_Python_v3.10
source scripts/TF_ZenDNN_setup_release.sh

cd ~/\$1
bash run_bench.sh \$NUM_CPU_PHYSICAL_CORES \$2 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > zendnn-tensorflow
chmod +x zendnn-tensorflow
