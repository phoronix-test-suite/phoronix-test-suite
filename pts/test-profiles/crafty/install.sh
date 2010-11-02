#!/bin/sh

unzip -o crafty-23.3.zip

cd crafty-23.3/

if [ $OS_TYPE = "MacOSX" ]
then
	make darwin
elif [ $OS_TYPE = "BSD" ]
then
	make freebsd
elif [ $OS_TYPE = "Solaris" ]
then
	make solaris-gcc
else
	make linux
fi

echo $? > ~/install-exit-status

cd ..

echo "#!/bin/sh
cd crafty-23.3/
./crafty \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > crafty
chmod +x crafty
