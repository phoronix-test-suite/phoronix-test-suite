#!/bin/sh
tar -xf miniBUDE-20210901.tar.xz
cd miniBUDE-20210901/openmp
make 
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd miniBUDE-20210901/openmp
./bude \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > minibude
chmod +x minibude
