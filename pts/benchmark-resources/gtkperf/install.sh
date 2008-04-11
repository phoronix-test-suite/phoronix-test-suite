#!/bin/sh

cd $1

if [ ! -f gtkperf_0.40.tar.gz ]
  then
     wget http://internap.dl.sourceforge.net/sourceforge/gtkperf/gtkperf_0.40.tar.gz -O gtkperf_0.40.tar.gz
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/gtkperf_env

tar -xvf gtkperf_0.40.tar.gz
cd gtkperf/
./configure --prefix=$THIS_DIR/gtkperf_env
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf gtkperf/

echo "#!/bin/sh
./gtkperf_env/bin/gtkperf \$@" > gtkperf
chmod +x gtkperf
