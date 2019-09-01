#!/bin/sh

unzip -o shoc-20151110
cd shoc-master
mkdir build
cd build

make distclean

CONFIG_STRING=""

if [ "$OS_ARCH" = "aarch64" ]
then
	CONFIG_STRING="--build=arm "
fi

if [ -d /usr/include/CL ]
then
	CONFIG_STRING=" --with-opencl $CONFIG_STRING"
fi

if [ -d /usr/local/cuda ]
then
	PATH="/usr/local/cuda/bin:$PATH"
fi

if which nvcc >/dev/null 2>&1 ;
then
	CONFIG_STRING=" --with-cuda $CONFIG_STRING"

	if [ "X$CUDA_CPPFLAGS" = "X" ]
	then
		CUDA_CPPFLAGS="-gencode=arch=compute_37,code=compute_37"
	fi

	CONFIG_STRING="$CONFIG_STRING CUDA_CPPFLAGS=\"$CUDA_CPPFLAGS\""
fi

../configure $CONFIG_STRING
make
make install
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd shoc-master/build
./bin/shocdriver \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > shoc
chmod +x shoc
