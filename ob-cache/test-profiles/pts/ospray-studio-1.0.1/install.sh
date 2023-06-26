#!/bin/sh

unzip -o OSPRayStudio-Room-Scene.zip
tar -xf ospray_studio-0.10.0.tar.gz

cd ospray_studio-0.10.0
sed -i 's/add_subdirectory(tests)/ /g' sg/CMakeLists.txt

mkdir build
cd build
cmake .. -DUSE_BENCHMARK=ON -DCMAKE_BUILD_TYPE=Release -DUSE_PYSG=OFF
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
export PATH=\$HOME/ospray_studio-0.10.0/build/:\$PATH

cd OSPRayStudio-Room-Scene/
ospStudio benchmark --denoiser --format jpg --forceRewrite \$@ RoomScene.sg > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray-studio
chmod +x ospray-studio
