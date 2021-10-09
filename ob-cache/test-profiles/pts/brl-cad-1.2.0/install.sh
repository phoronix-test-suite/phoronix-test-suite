#!/bin/sh

tar -xf brlcad-7.32.2.tar.bz2

cp brlcad-7.32.2/src/other/libpng/scripts/pnglibconf.h.prebuilt brlcad-7.32.2/src/other/libpng/pnglibconf.h
mkdir brlcad-7.32.2/build
cd brlcad-7.32.2/build
cmake .. -DBRLCAD_ENABLE_STRICT=NO -DBRLCAD_BUNDLED_LIBS=ON -DBRLCAD_OPTIMIZED_BUILD=ON -DBRLCAD_PNG=OFF -DCMAKE_BUILD_TYPE=Release -DBRLCAD_OPTIMIZED_BUILD=ON

# Build fixes
echo "#include <limits>" > new_eartcut.hpp
cat ../src/libbg/earcut.hpp >> new_eartcut.hpp
cat new_eartcut.hpp > ../src/libbg/earcut.hpp
echo "#include <limits>" > brep.hpp
cat ../src/libged/brep/brep.cpp >> brep.hpp
cat brep.hpp > ../src/libged/brep/brep.cpp

if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd brlcad-7.32.2/build
./bench/benchmark run -P\$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > brl-cad
chmod +x brl-cad


