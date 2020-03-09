#!/bin/sh

# Not all of these dependencies below may be covered automatically by PTS

rm -rf cifar10
tar -axf cifar10_tf.tar.gz

pip3 install tensorflow

echo "#!/bin/sh
cd cifar10
python3 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tensorflow
chmod +x tensorflow
