#!/bin/sh

tar -xf NAB-20181109.tar.xz
cd NAB-master
pip install . --user
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd NAB-master
python run.py -d numenta --detect --skipConfirmation --score
echo \$? > ~/test-exit-status" > numenta-nab

chmod +x numenta-nab
