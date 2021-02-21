#!/bin/sh

mkdir lib
tar -zxvf pyopencl-0.91.3.tar.gz
tar -zxvf pyopencl-2009-10-03.tgz
cd pyopencl-0.91.3

PYTHON_V1=`python -c "import sys;v=sys.version_info;print v[0]"`
PYTHON_V2=`python -c "import sys;v=sys.version_info;print v[1]"`
./configure.py \
	--boost-python-libname=boost_python-py${PYTHON_V1}${PYTHON_V2}-mt \
	--boost-thread-libname=boost_thread-mt
make 2>&1
echo \$? > ~/test-exit-status

cp -a build/lib.linux-x86_64-${PYTHON_V1}.${PYTHON_V2}/pyopencl ../lib
cd ..
echo "#/bin/sh
export PYTHONPATH=`pwd`/lib
python transpose.py \$@ | tail -n 1 > \$LOG_FILE 2>&1
" > pyopencl

chmod +x pyopencl

