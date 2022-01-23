#!/bin/sh

tar -zxf tachyon-0.99.2.tar.gz
cd tachyon/unix/

if [ $OS_TYPE = "MacOSX" ]
then
	make macosx-x86-thr
	echo $? > ~/install-exit-status
	cd ..
	ln -s compile/macosx-x86-thr/tachyon tachyon

else
	make linux-64-thr
	echo $? > ~/install-exit-status
	cd ..
	ln -s compile/linux-64-thr/tachyon tachyon
fi

cd ~

echo "#!/bin/sh
cd tachyon/
./tachyon scenes/teapot.dat -numthreads \$NUM_CPU_CORES -fullshade -shade_phong -trans_vmd -shadow_filter_on -aasamples 32 -res 8192 8192 > \$LOG_FILE 2>&1
rm -f outfile.tga" > tachyon-benchmark
chmod +x tachyon-benchmark
