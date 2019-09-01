#!/bin/sh

tar -xf radiance-5.0.0-Linux64.tar.gz
unzip -o Radiance-Benchmark4-20180624.zip

echo "#!/bin/sh
cd Radiance-Benchmark4-master
NCPUS=\$NUM_CPU_CORES RAYPATH=.:\$HOME/radiance-5.0.0-Linux/usr/local/radiance/lib PATH=\$HOME/radiance-5.0.0-Linux/usr/local/radiance/bin:$PATH \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > radiance
chmod +x radiance
