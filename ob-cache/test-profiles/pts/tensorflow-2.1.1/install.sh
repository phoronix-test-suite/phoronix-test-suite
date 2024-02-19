#!/bin/sh
pip3 install --user tensorflow-cpu==2.12
echo $? > ~/install-exit-status
unzip -o tensorflow-benchmarks-20220925.zip
echo "#!/bin/sh
cd tensorflow-benchmarks/scripts/tf_cnn_benchmarks/
python3 tf_cnn_benchmarks.py \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tensorflow
chmod +x tensorflow
