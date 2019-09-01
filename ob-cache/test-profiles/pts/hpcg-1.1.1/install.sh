#!/bin/sh

tar -zxvf hpcg-3.0.tar.gz
cd hpcg-3.0
make arch=GCC_OMP
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd hpcg-3.0/bin/
rm -f *.yaml
./xhpcg
echo \$? > ~/test-exit-status

cat *.yaml > \$LOG_FILE
rm -f *.yaml" > hpcg
chmod +x hpcg
