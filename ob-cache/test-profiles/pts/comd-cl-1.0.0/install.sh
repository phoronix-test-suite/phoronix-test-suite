#!/bin/sh

tar -xjvf ComputeApps-20170706.tar.bz2
cd ComputeApps/comd-cl/src-cl
make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ComputeApps/comd-cl
./CoMD-ocl \$@ > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > comd-cl
chmod +x comd-cl
