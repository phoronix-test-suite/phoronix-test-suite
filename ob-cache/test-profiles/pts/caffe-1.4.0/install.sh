#!/bin/sh

# Not all of these dependencies below may be covered automatically by PTS
# sudo apt-get install libprotobuf-dev libleveldb-dev libsnappy-dev libopencv-dev libhdf5-serial-dev protobuf-compiler
# sudo apt-get install libgflags-dev libgoogle-glog-dev liblmdb-dev libatlas-base-dev
# sudo apt-get install --no-install-recommends libboost-all-dev

rm -rf caffe-git
tar -xf caffe-git-20181229.tar.xz
cd caffe-git

if [ -d /usr/local/cuda ]
then
	PATH="/usr/local/cuda/bin:$PATH"
	LD_LIBRARY_PATH=/usr/local/cuda/lib:$LD_LIBRARY_PATH
fi

if which nvcc >/dev/null 2>&1 ;
then
	cp -f Makefile.config.nvidia Makefile.config
	make -j$NUM_CPU_CORES all
fi

cp -f Makefile.config.cpu Makefile.config

if [ -e /usr/lib64/libopenblas.so ]
then
	sed -i -e "s/BLAS :=.*/BLAS := open/g" Makefile.config
fi
if [ -e /usr/lib64/libopencv_core.so.3.1 ]
then
	sed -i -e "s/# OPENCV_VERSION := 3/OPENCV_VERSION := 3/g" Makefile.config
fi
if [ -e /usr/lib64/libopencv_core.so.3.2 ]
then
	sed -i -e "s/# OPENCV_VERSION := 3/OPENCV_VERSION := 3/g" Makefile.config
fi
if [ -e /usr/lib64/libopencv_core.so.3.4 ]
then
	sed -i -e "s/# OPENCV_VERSION := 3/OPENCV_VERSION := 3/g" Makefile.config
fi
if [ -e /usr/lib64/libopencv_core.so.4.0 ]
then
	sed -i -e "s/# OPENCV_VERSION := 3/OPENCV_VERSION := 4/g" Makefile.config
fi

make -j$NUM_CPU_CORES all
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd caffe-git
export LD_LIBRARY_PATH=/usr/local/cuda/lib64:\$LD_LIBRARY_PATH
./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > caffe
chmod +x caffe
