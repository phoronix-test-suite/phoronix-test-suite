#!/bin/sh
tar -xf brlcad-7.36.0.tar.bz2
cp brlcad-7.36.0/src/other/libpng/scripts/pnglibconf.h.prebuilt brlcad-7.36.0/src/other/libpng/pnglibconf.h
mkdir brlcad-7.36.0/build
cd brlcad-7.36.0/build
cmake .. -DBRLCAD_ENABLE_STRICT=NO -DBRLCAD_BUNDLED_LIBS=ON -DBRLCAD_OPTIMIZED=ON -DBRLCAD_PNG=OFF -DCMAKE_BUILD_TYPE=Release -DBRLCAD_WARNINGS=OFF
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd brlcad-7.36.0/build
./bench/benchmark run -P\$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > brl-cad
chmod +x brl-cad
