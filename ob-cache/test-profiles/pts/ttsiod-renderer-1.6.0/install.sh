#!/bin/sh

unzip -o renderer-2.3a.zip

cd renderer-2.3a/
./configure
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd renderer-2.3a/3D-Objects
OMP_NUM_THREADS=\$NUM_CPU_CORES SDL_VIDEODRIVER=dummy ../src/renderer \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ttsiod-renderer
chmod +x ttsiod-renderer
