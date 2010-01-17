#!/bin/sh

tar -zxvf john-1.7.3.1.tar.gz
cd john-1.7.3.1/src/

case $OS_TYPE in
	"MacOSX")
		make macosx-x86-64
	;;
	"Solaris")
		if [ $OS_ARCH = "x86_64" ]
		then
			make solaris-x86-64-gcc
		else
			make solaris-x86-sse2-gcc
		fi
	;;
	"BSD")
		if [ $OS_ARCH = "x86_64" ]
		then
			make freebsd-x86-64
		else
			make freebsd-x86-sse2
		fi
	;;
	*)
		if [ $OS_ARCH = "x86_64" ]
		then
			make linux-x86-64
		else
			make linux-x86-sse2
		fi
	;;
esac

cd ../../

echo "#!/bin/sh
cd john-1.7.3.1/run/
./john --test > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > john-the-ripper
chmod +x john-the-ripper
