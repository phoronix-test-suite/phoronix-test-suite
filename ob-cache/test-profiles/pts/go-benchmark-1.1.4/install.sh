#!/bin/sh

export GOPATH=$HOME/gobench
TESTDIR=$GOPATH/src/golang.org
mkdir -p $TESTDIR
tar -xf golang-benchmarks-121017.tar.gz -C $TESTDIR
cd $TESTDIR
mv go-benchmark-04122017/x x
cd ~
GOBIN=$PWD go install golang.org/x/benchmarks/...

cd ~
echo "#!/bin/sh
./\$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > go-benchmark
chmod +x go-benchmark
