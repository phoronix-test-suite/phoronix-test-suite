#!/bin/sh

export GOPATH=$DEBUG_HOME\\gobench
TESTDIR=$GOPATH/src/golang.org
mkdir -p $TESTDIR
tar -xf golang-benchmarks-121017.tar.gz -C $TESTDIR
cd $TESTDIR
mv go-benchmark-04122017/x x
cd ~
GOBIN=$DEBUG_HOME /cygdrive/c/Go/bin/go.exe install golang.org/x/benchmarks/... 2>&1

cd ~
echo "#!/bin/bash
./\$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > go-benchmark
chmod +x go-benchmark
