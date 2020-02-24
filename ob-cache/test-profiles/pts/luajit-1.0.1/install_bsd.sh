#!/bin/sh

unzip LuaJIT-2.0.5.zip
cd LuaJIT-2.0.5
gmake -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./LuaJIT-2.0.5/src/luajit scimark.lua -large > \$LOG_FILE 2>&1" > luajit
chmod +x luajit
