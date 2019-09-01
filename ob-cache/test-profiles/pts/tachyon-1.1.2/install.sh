#!/bin/sh

tar -zxvf tachyon-0.98.9.tar.gz
cd tachyon/unix/

if [ $OS_TYPE = "MacOSX" ]
then
	make macosx-x86-thr
	cd ..
	ln -s compile/macosx-x86-thr/tachyon tachyon

else
	make linux-thr
	cd ..
	ln -s compile/linux-thr/tachyon tachyon
fi

cd ~

echo "#!/bin/sh
cd tachyon/
./tachyon scenes/teapot.dat -numthreads \$NUM_CPU_CORES -fullshade -shade_phong -trans_vmd -aasamples 32 -res 1080 1080 > \$LOG_FILE 2>&1" > tachyon-benchmark
chmod +x tachyon-benchmark
