#!/bin/sh

tar -xf luaradio-0.9.1.tar.gz
tar -xf luaradio_benchmark_modified-1.tar.xz
mv luaradio_benchmark_modified.lua luaradio-0.9.1

echo "#!/bin/sh
cd luaradio-0.9.1
./luaradio luaradio_benchmark_modified.lua > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > luaradio
chmod +x luaradio
