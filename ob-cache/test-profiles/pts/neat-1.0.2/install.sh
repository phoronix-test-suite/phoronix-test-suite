#!/bin/sh

tar -xf neat-git-20200229.tar.xz
cd neat-git-20200229/
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
cd neat-git-20200229/
t/bin/neat -i examples/ngc7009_all.dat -u -n 30000 > \$LOG_FILE
echo \$? > ~/test-exit-status" > neat
chmod +x neat
