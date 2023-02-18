#!/bin/sh
tar -xf QuadRay-engine-20220525.tar.xz
cd QuadRay-engine/root
# For now only support x86_64 but other archs could be easily supported as handled by upstream... Patches to this script welcome
make -f RooT_make_x64.mk -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd QuadRay-engine/root
./RooT.x64f64 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > quadray
chmod +x quadray
