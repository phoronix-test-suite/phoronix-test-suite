#!/bin/sh

tar -zxvf john-1.8.0.tar.gz
cd john-1.8.0/src/

case $OS_TYPE in
	"MacOSX")
		make OMPFLAGS=-fopenmp macosx-x86-64
	;;
	"Solaris")
		if [ $OS_ARCH = "x86_64" ]
		then
			make OMPFLAGS=-fopenmp solaris-x86-64-gcc
		elif [ $OS_ARCH = "i686" ]
		then
			make OMPFLAGS=-fopenmp solaris-x86-sse2-gcc
		else
			make OMPFLAGS=-fopenmp generic
		fi
	;;
	"BSD")
		if [ $OS_ARCH = "x86_64" ]
		then
			make OMPFLAGS=-fopenmp freebsd-x86-64
		elif [ $OS_ARCH = "i686" ]
		then
			make OMPFLAGS=-fopenmp freebsd-x86-sse2
		else
			make OMPFLAGS=-fopenmp generic
		fi
	;;
	*)
		if [ $OS_ARCH = "x86_64" ]
		then
			make OMPFLAGS=-fopenmp linux-x86-64
		elif [ $OS_ARCH = "i686" ]
		then
			make OMPFLAGS=-fopenmp linux-x86-sse2
		else
			make OMPFLAGS=-fopenmp generic
		fi
	;;
esac

cd ~/

echo "#!/bin/sh
cd john-1.8.0/run/
./john --test > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > john-the-ripper
chmod +x john-the-ripper
