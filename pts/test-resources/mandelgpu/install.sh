#!/bin/sh

unzip -o MandelGPU-v1.3pts.zip
cd MandelGPU-v1.3pts/
make
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd MandelGPU-v1.3pts/
./mandelGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mandelgpu
chmod +x mandelgpu
