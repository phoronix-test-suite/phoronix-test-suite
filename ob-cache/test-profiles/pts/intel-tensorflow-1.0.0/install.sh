#!/bin/sh
# imagenet URLs @ https://gist.github.com/bonlime/4e0d236cf98cd5b15d977dfa03a63643
pip3 install --user intel-tensorflow==2.12
unzip -o models-92bc6044e5047a2ca55920d9cfec3bcf9a8bf23d.zip 

if [ ! -d "imageraw" ] 
then
	mkdir imageraw
	cd imageraw
	ln -s ../ILSVRC2012_img_val.tar ILSVRC2012_img_val.tar
	ln -s ../ILSVRC2012_img_train.tar ILSVRC2012_img_train.tar
	~/models-92bc6044e5047a2ca55920d9cfec3bcf9a8bf23d/datasets/imagenet/imagenet_to_tfrecords.sh ~/imageraw/
	cd ~
fi

echo "#!/bin/bash
cd ~/models-92bc6044e5047a2ca55920d9cfec3bcf9a8bf23d/
export PRETRAINED_MODEL=\$HOME/\$1
export DATASET_DIR=~/imageraw/tf_records/
if [[ \$PRETRAINED_MODEL == *\"int8\"* ]]; then
  export PRECISION=int8
else
  export PRECISION=fp32
fi
if [[ \$PRETRAINED_MODEL == *\"resnet\"* ]]; then
  export SCRIPT_CMD=resnet50/inference/cpu/batch_inference.sh
elif [[ \$PRETRAINED_MODEL == *\"inceptionv4\"* ]]; then
  export SCRIPT_CMD=inceptionv4/inference/cpu/batch_inference.sh
elif [[ \$PRETRAINED_MODEL == *\"mobilenetv1\"* ]]; then
  export SCRIPT_CMD=mobilenet_v1/inference/cpu/inference_throughput_multi_instance.sh
else
  echo \"ERROR: No matching type\" > \$LOG_FILE
  echo 2 > ~/test-exit-status
  exit 2
fi
export OUTPUT_DIR=/tmp
export BATCH_SIZE=\$2

if [ \"\${BATCH_SIZE}\" -eq 1 ] && [[ \$PRETRAINED_MODEL == *\"mobilenetv1\"* ]]; then
  export SCRIPT_CMD=mobilenet_v1/inference/cpu/inference_realtime_multi_instance.sh
fi
./quickstart/image_recognition/tensorflow/\$SCRIPT_CMD > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > intel-tensorflow
chmod +x intel-tensorflow
