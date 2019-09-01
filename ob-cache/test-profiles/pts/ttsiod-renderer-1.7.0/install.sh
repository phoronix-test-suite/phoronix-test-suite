#!/bin/sh

unzip -o renderer-2.3b.zip

cd renderer-2.3b/
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd renderer-2.3b/3D-Objects
OMP_NUM_THREADS=\$NUM_CPU_CORES SDL_VIDEODRIVER=dummy ../src/renderer \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ttsiod-renderer
chmod +x ttsiod-renderer
