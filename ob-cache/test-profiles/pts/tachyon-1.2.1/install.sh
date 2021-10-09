#!/bin/sh

tar -zxf tachyon-0.99b6.tar.gz
cd tachyon/unix/

if [ $OS_TYPE = "MacOSX" ]
then
	make macosx-x86-thr
	cd ..
	ln -s compile/macosx-x86-thr/tachyon tachyon

else
	make linux-64-thr
	cd ..
	ln -s compile/linux-64-thr/tachyon tachyon
fi

cd ~

echo "#!/bin/sh
cd tachyon/
./tachyon scenes/teapot.dat -numthreads \$NUM_CPU_CORES -fullshade -shade_phong -trans_vmd -shadow_filter_on -aasamples 32 -res 6144 6144 > \$LOG_FILE 2>&1" > tachyon-benchmark
chmod +x tachyon-benchmark
