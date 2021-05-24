#!/bin/sh

tar -xf NEAT-2.3.tar.gz
cd NEAT-2.3

# Need fallow-argument-mismatch to build NEAT 2.3 with GCC 10
export FFLAGS="-O3 -fallow-argument-mismatch"
export DESTDIR=""

sed -i 's,PREFIX=/usr,PREFIX='"$HOME"',' Makefile

if [ $OS_TYPE = "BSD" ]
then
	gmake
	gmake install
else
	make
	echo $? > ~/install-exit-status
	make install
fi

cd ~
echo "#!/bin/sh
cd NEAT-2.3
~/bin/neat -i examples/ngc7009_all.dat -u -n 60000 > \$LOG_FILE
echo \$? > ~/test-exit-status" > neat
chmod +x neat
