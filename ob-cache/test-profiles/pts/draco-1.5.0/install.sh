#!/bin/sh

tar -xf draco-1.5.0.tar.gz
cd draco-1.5.0
mkdir build
cd build
cmake -DCMAKE_BUILD_TYPE=Release ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
unzip -o church-facade-ply.zip
mv Church\ façade.ply draco-1.5.0/build/church.ply
unzip -o lion-statue_ply.zip
mv Lion\ statue_ply/Lion\ statue.ply draco-1.5.0/build/lion.ply

cd ~
echo "#!/bin/sh
cd draco-1.5.0/build
./draco_encoder \$@ -o out.drc -cl 10 -qp 14 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > draco
chmod +x draco
