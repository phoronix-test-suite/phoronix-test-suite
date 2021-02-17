#!/bin/bash

tar -xf amd-stream-dynamic-1.tar.xz
cd amd-stream-dynamic

if [[ -x "/opt/AMD/setenv_AOCC.sh" ]]
then
	export CC=clang
	export CXX=clang++
elif which clang >/dev/null 2>&1 ;
then
	export CC=clang
	export CXX=clang++
else
	export CC=cc
	export CXX=c++
fi


./build_stream_dynamic.py
echo \$? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd amd-stream-dynamic
./run_stream_dynamic.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > stream-dynamic
chmod +x stream-dynamic
