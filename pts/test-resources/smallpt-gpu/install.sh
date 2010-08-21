#!/bin/sh

unzip -o SmallptGPU-v1.6pts.zip
cd SmallptGPU-v1.6pts/
make
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd SmallptGPU-v1.6pts/
./smallptGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > smallpt-gpu
chmod +x smallpt-gpu
