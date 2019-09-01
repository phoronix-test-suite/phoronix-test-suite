#!/bin/sh

tar -xf LuaJIT-20190110.tar.xz
cd LuaJIT-Git
gmake -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./LuaJIT-Git/src/luajit scimark.lua -large > \$LOG_FILE 2>&1" > luajit
chmod +x luajit
