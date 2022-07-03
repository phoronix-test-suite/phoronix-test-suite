#!/bin/sh

rm -rf glibc-2.35
tar -xf glibc-2.35.tar.xz

cd glibc-2.35
mkdir build
cd build
../configure  --disable-sanity-checks CFLAGS="-O3 $CFLAGS"
make -j $NUM_CPU_CORES
make bench-build
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd glibc-2.35/build/benchtests/
CONV_PATH=\$HOME/glibc-2.35/build/iconvdata LOCPATH=\$HOME/glibc-2.35/build/localedata LC_ALL=C   \$HOME/glibc-2.35/build/elf/ld.so --library-path \$HOME/glibc-2.35/build:\$HOME/glibc-2.35/build/math:\$HOME/glibc-2.35/build/elf:\$HOME/glibc-2.35/build/dlfcn:\$HOME/glibc-2.35/build/nss:\$HOME/glibc-2.35/build/nis:\$HOME/glibc-2.35/build/rt:\$HOME/glibc-2.35/build/resolv:\$HOME/glibc-2.35/build/mathvec:\$HOME/glibc-2.35/build/support:\$HOME/glibc-2.35/build/crypt:\$HOME/glibc-2.35/build/nptl ./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > glibc-bench
chmod +x glibc-bench
