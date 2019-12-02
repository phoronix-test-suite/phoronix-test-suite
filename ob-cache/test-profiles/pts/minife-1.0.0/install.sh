#!/bin/sh

tar -xf miniFE-2.2.0.tar.gz
cd miniFE-2.2.0/openmp/src
make 
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd miniFE-2.2.0/openmp/src
rm -f *.yaml
./miniFE.x \$@
echo \$? > ~/test-exit-status
cat *.yaml > \$LOG_FILE" > minife
chmod +x minife
