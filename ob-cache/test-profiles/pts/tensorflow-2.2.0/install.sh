#!/bin/sh
pip3 install --user tensorflow-cpu==2.16.1
echo $? > ~/install-exit-status
unzip -o tensorflow-benchmarks-8fb79079265933ad39bea628b777af662f5bf15d.zip
# Compatibility workarounds for TensorFlow 2.16...
sed -i '126d' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/models/experimental/deepspeech.py
sed -i '126d' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/models/experimental/deepspeech.py
sed -i '126d' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/models/experimental/deepspeech.py
sed -i 's/use_tf_layers = use_tf_layers/use_tf_layers = False/g' benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/convnet_builder.py
echo "#!/bin/sh
cd benchmarks-8fb79079265933ad39bea628b777af662f5bf15d/scripts/tf_cnn_benchmarks/
python3 tf_cnn_benchmarks.py \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tensorflow
chmod +x tensorflow


