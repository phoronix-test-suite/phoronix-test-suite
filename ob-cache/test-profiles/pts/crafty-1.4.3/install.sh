#!/bin/sh

unzip -o crafty-25.2.zip


if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

export CC="cc"
export CXX="c++"
export CFLAGS="-Wall -pipe -fomit-frame-pointer $CFLAGS-j $NUM_CPU_CORES"
export CXFLAGS="-Wall -pipe -O3 -fomit-frame-pointer -j NUM_CPU_CORES"
export LDFLAGS="$LDFLAGS -pthread -lstdc++"
# sed -i ".orig" -e 's/-j /-j4 /g' Makefile
if which gcc >/dev/null; then
    make unix-gcc
else
    make unix-clang
fi

echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./crafty \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > crafty-benchmark
chmod +x crafty-benchmark
