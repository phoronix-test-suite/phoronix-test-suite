#!/bin/sh

tar -xf chiavdf-1.0.7.tar.gz
cd chiavdf-1.0.7
python3 setup.py build
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd chiavdf-1.0.7
./src/vdf_bench \$@ 9000000 > \$LOG_FILE
echo \$? > ~/test-exit-status" > chia-vdf
chmod +x chia-vdf
