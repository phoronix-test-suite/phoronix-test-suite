#!/bin/bash

tar xf Stockfish-sf_13.tar.gz
cd Stockfish-sf_13/src/

if [ $OS_ARCH = "x86_64" ]
then
	# find the CPU architecture
	gcc_enabled=$(c++ -Q -march=native --help=target | grep "\[enabled\]")
	gcc_arch=$(c++ -Q -march=native --help=target | grep "march")

	if [[ "${gcc_enabled}" =~ "-mavx512vnni " && "${gcc_enabled}" =~ "-mavx512dq " && "${gcc_enabled}" =~ "-mavx512f " && "${gcc_enabled}" =~ "-mavx512bw " && "${gcc_enabled}" =~ "-mavx512vl " ]] ; then
	  ARCH="x86-64-vnni256"
	elif [[ "${gcc_enabled}" =~ "-mavx512f " && "${gcc_enabled}" =~ "-mavx512bw " ]] ; then
	  ARCH="x86-64-avx512"
	elif [[ "${gcc_enabled}" =~ "-mbmi2 " && ! "${gcc_arch}" =~ "znver1" && ! "${gcc_arch}" =~ "znver2" ]] ; then
	  ARCH="x86-64-bmi2"
	elif [[ "${gcc_enabled}" =~ "-mavx2 " ]] ; then
	  ARCH="x86-64-avx2"
	elif [[ "${gcc_enabled}" =~ "-mpopcnt " && "${gcc_enabled}" =~ "-msse4.1 " ]] ; then
	  ARCH="x86-64-modern"
	elif [[ "${gcc_enabled}" =~ "-mssse 3 " ]] ; then
	  ARCH="x86-64-ssse3"
	elif [[ "${gcc_enabled}" =~ "-mpopcnt " && "${gcc_enabled}" =~ "-msse3 " ]] ; then
	  ARCH="x86-64-sse3-popcnt"
	else
	  ARCH="x86-64-avx2"
	fi
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
	gmake profile-build ARCH=$ARCH
else
	make profile-build ARCH=$ARCH

fi
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd Stockfish-sf_13/src/
./stockfish bench 128 \$NUM_CPU_CORES 24 default depth > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > stockfish
chmod +x stockfish
