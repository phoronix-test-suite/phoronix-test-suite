#!/bin/sh

tar -xf shoc-master-20200417.tar.xz
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

# if [ -d /usr/local/cuda ]
# then
#	PATH="/usr/local/cuda/bin:$PATH"
# fi

# Disable the  CUDA support for now due to upstream build issues...
if which FAILMEnvcc >/dev/null 2>&1 ;
then
	CONFIG_STRING=" --with-cuda $CONFIG_STRING"

	if [ "X$CUDA_CPPFLAGS" = "X" ]
	then
		CUDA_CPPFLAGS="-gencode=arch=compute_60,code=sm_60 -gencode=arch=compute_61,code=sm_61 -gencode=arch=compute_62,code=sm_62 -gencode=arch=compute_70,code=sm_70 -gencode=arch=compute_72,code=sm_72 -gencode=arch=compute_75,code=sm_75 -gencode=arch=compute_80,code=sm_80 -gencode=arch=compute_86,code=sm_86"
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
