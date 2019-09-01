#!/bin/sh

rm -rf parboil-2.5-tree

tar -zxvf pb2.5driver.tgz
tar -zxvf pb2.5datasets_standard.tgz
tar -zxvf pb2.5benchmarks.tgz

mv datasets parboil/
mv benchmarks parboil/
mv parboil parboil-2.5-tree/

cd parboil-2.5-tree/
touch common/Makefile.conf # TODO: add OpenCL/CUDA support here to use more benchmarks...

COMPILE_OPENCL=0

if [ -d /opt/AMDAPP/ ]
then
	echo "OPENCL_PATH=/opt/AMDAPP/
OPENCL_LIB_PATH=/opt/AMDAPP/lib/x86_64" >> common/Makefile.conf
	COMPILE_OPENCL=1
elif [ -f /usr/lib/libOpenCL.so.1 ]
then
	echo "OPENCL_PATH=/usr/
OPENCL_LIB_PATH=/usr/lib" >> common/Makefile.conf
	COMPILE_OPENCL=1
elif [ -d /usr/local/cuda ]
then
	echo "OPENCL_PATH=/usr/local/cuda
OPENCL_LIB_PATH=/usr/lib" >> common/Makefile.conf
	COMPILE_OPENCL=1
elif [ -d /usr/local/cuda-5.5 ]
then
	echo "OPENCL_PATH=/usr/local/cuda-5.5
OPENCL_LIB_PATH=/usr/lib" >> common/Makefile.conf
	COMPILE_OPENCL=1
elif [ -d /usr/local/cuda-6.0 ]
then
	echo "OPENCL_PATH=/usr/local/cuda-6.0
OPENCL_LIB_PATH=/usr/lib" >> common/Makefile.conf
	COMPILE_OPENCL=1
fi

echo "CC = cc
PLATFORM_CFLAGS = -O3 -ffast-math
  
CXX = c++
PLATFORM_CXXFLAGS = -O3 -ffast-math
  
LINKER = c++
PLATFORM_LDFLAGS = -lm -lpthread
" > common/platform/c.default.mk

if which python2 >/dev/null; then
	find . -type f -print0 | xargs -0 sed -i '' -e 's/env python/env python2/g'
	PYTHON_BIN=python2
elif which python >/dev/null; then
	PYTHON_BIN=python
else
    echo "ERROR: Cannot find Python"
fi

if [ $COMPILE_CL = 1 ]
then
	$PYTHON_BIN ./parboil compile bfs opencl_base
	$PYTHON_BIN ./parboil compile tpacf opencl_base
	$PYTHON_BIN ./parboil compile lbm opencl_base
	$PYTHON_BIN ./parboil compile lbm opencl_base
	$PYTHON_BIN ./parboil compile mri-gridding opencl_base
	$PYTHON_BIN ./parboil compile histo opencl_base
fi

$PYTHON_BIN ./parboil compile cutcp omp_base
$PYTHON_BIN ./parboil compile mri-q omp_base
$PYTHON_BIN ./parboil compile mri-gridding omp_base
$PYTHON_BIN ./parboil compile stencil omp_base small
$PYTHON_BIN ./parboil compile lbm omp_cpu
$PYTHON_BIN ./parboil compile tpacf omp_base

echo $? > ~/test-exit-status
cd ~/

echo "#!/bin/sh
cd parboil-2.5-tree/
$PYTHON_BIN ./parboil run \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > parboil
chmod +x parboil
