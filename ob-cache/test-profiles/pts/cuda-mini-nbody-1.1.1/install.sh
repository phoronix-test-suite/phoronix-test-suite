#!/bin/sh

unzip -o mini-nbody-20151110.zip
cd mini-nbody-master/cuda

cd ~/
echo "#!/bin/sh

if [ -d /usr/local/cuda ]
then
	PATH=\"/usr/local/cuda/bin:\$PATH\"
	LD_LIBRARY_PATH=/usr/local/cuda/lib64:\$LD_LIBRARY_PATH
fi

cd mini-nbody-master/cuda
bash \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > cuda-mini-nbody
chmod +x cuda-mini-nbody
