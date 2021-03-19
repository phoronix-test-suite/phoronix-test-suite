#!/bin/sh

unzip -o Stockfish-sf_12.zip
cd Stockfish-sf_12/src/

if [ $OS_ARCH = "x86_64" ]
then
	ARCH=x86-64-avx2
elif [ $OS_ARCH = "ppc64" ]
then
	ARCH=ppc-64
elif [ $OS_ARCH = "i686" ]
then
	ARCH=x86-32
elif [ $OS_ARCH = "armv7" ]
then
	ARCH=armv7
elif [ $OS_ARCH = "aarch64" ]
then
	ARCH=armv8
else
	ARCH=general-64
fi

if [ $OS_TYPE = "BSD" ]
then
	gmake build ARCH=$ARCH
else
	make build ARCH=$ARCH

fi
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd Stockfish-sf_12/src/
./stockfish bench 128 \$NUM_CPU_CORES 24 default depth > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > stockfish
chmod +x stockfish
