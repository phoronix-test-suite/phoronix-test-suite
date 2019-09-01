#!/bin/sh

tar -xjvf ComputeApps-20170706.tar.bz2
cd ComputeApps/lulesh-cl
make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ComputeApps/lulesh-cl
./lulesh \$@ > \$LOG_FILE 2> /dev/null
echo \$? > ~/test-exit-status" > lulesh-cl
chmod +x lulesh-cl
