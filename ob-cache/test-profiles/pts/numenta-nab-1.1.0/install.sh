#!/bin/sh

tar -xf NAB-1.1.tar.gz
cd NAB-1.1
pip3 install -r requirements.txt --user
pip3 install . --user
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd NAB-1.1
python3 run.py \$@ -n \$NUM_CPU_CORES
echo \$? > ~/test-exit-status" > numenta-nab

chmod +x numenta-nab
