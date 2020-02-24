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

if [ $COMPILE_CL = 1 ]
then
	./parboil compile bfs opencl_base
	./parboil compile tpacf opencl_base
	./parboil compile lbm opencl_base
	./parboil compile lbm opencl_base
	./parboil compile mri-gridding opencl_base
	./parboil compile histo opencl_base
fi

./parboil compile cutcp omp_base
./parboil compile mri-q omp_base
./parboil compile mri-gridding omp_base
./parboil run stencil omp_base small
./parboil compile lbm omp_cpu
./parboil compile tpacf omp_base

echo $? > ~/test-exit-status
cd ~/

echo "#!/bin/sh
cd parboil-2.5-tree/
./parboil run \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > parboil
chmod +x parboil
