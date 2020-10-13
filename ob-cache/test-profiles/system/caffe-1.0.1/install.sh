#!/bin/sh

if which caffe >/dev/null 2>&1 ;
then
    echo 0 > ~/install-exit-status
else
    echo "ERROR: Caffe is not found on the system!"
    echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
caffe time --model=\$TEST_EXTENDS/caffe-git/models/bvlc_alexnet/deploy.prototxt -iterations 1000 >\$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > caffe-benchmark
chmod +x caffe-benchmark
