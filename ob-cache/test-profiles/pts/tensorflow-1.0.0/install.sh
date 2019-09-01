#!/bin/sh

# Not all of these dependencies below may be covered automatically by PTS
# sudo apt-get install libprotobuf-dev libleveldb-dev libsnappy-dev libopencv-dev libhdf5-serial-dev protobuf-compiler
# sudo apt-get install libgflags-dev libgoogle-glog-dev liblmdb-dev libatlas-base-dev
# sudo apt-get install --no-install-recommends libboost-all-dev

rm -rf cifar10
tar -axf cifar10_tf.tar.gz

pip3 install tensorflow

echo "#!/bin/sh
cd cifar10
python3 cifar10_train.py  --max_steps \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tensorflow
chmod +x tensorflow
