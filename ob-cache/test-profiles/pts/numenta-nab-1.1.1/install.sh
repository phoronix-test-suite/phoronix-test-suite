#!/bin/sh
tar -xf NAB-1.1.tar.gz
cd NAB-1.1
pip3 install --user cython pgen pandas==1.5.2 simplejson==3.11.1 boto3==1.9.134 scikit-learn==1.2.0 plotly==2.0.0
pip3 install . --user --no-deps
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd NAB-1.1
python3 run.py \$@ -n \$NUM_CPU_CORES
echo \$? > ~/test-exit-status" > numenta-nab
chmod +x numenta-nab
