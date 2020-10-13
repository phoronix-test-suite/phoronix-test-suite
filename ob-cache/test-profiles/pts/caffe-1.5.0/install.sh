#!/bin/sh

rm -rf caffe-git
tar -xf caffe-git-20200213.tar.xz
cd caffe-git

mkdir build
cd build

CPU_ONLY=""
if [ ! -d /usr/local/cuda ]
then
	CPU_ONLY="-DCPU_ONLY=ON "
fi

cmake -DBUILD_python=OFF -DUSE_OPENCV=OFF -DBLAS=open -DUSE_LEVELDB=OFF $CPU_ONLY ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd caffe-git/build
./tools/caffe time \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > caffe
chmod +x caffe
