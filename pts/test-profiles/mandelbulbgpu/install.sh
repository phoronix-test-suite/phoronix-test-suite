#!/bin/sh

unzip -o mandelbulbGPU-v1.0pts.zip
cd mandelbulbGPU-v1.0pts/
make
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd mandelbulbGPU-v1.0pts/
./mandelbulbGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mandelbulbgpu
chmod +x mandelbulbgpu
