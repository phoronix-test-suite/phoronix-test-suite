#!/bin/sh

unzip -o JuliaGPU-v1.2pts.zip
cd JuliaGPU-v1.2pts/
make
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd JuliaGPU-v1.2pts/
./juliaGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > juliagpu
chmod +x juliagpu
