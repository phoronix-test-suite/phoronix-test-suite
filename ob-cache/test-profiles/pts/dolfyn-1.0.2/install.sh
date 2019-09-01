#!/bin/sh

tar -xzvf dolfyn-cfd_0.527.tgz
cd dolfyn-cfd_0.527/src/
if [ $OS_TYPE = "BSD" ]
then
	gmake
else
	make
fi
echo $? > ~/test-exit-status

cd ~
echo "#!/bin/sh
cd dolfyn-cfd_0.527/demo/
./doit.sh 2>&1
echo \$? > ~/test-exit-status" > dolfyn
chmod +x dolfyn
