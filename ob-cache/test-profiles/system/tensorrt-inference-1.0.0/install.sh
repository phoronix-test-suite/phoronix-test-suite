#!/bin/sh

if [ -x /usr/src/tensorrt/bin/giexec ]
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: NVIDIA TensorRT must be installed on the system where /usr/src/tensorrt/bin/giexec is present."
	echo 2 > ~/install-exit-status
fi

unzip -o TRT_prototxt-20181223T214740Z-001.zip


echo "#!/bin/bash

cd TRT_prototxt
/usr/src/tensorrt/bin/giexec \$@ > tensorrt-output 2>&1
echo \$? > ~/test-exit-status

BATCH_SIZE=\`echo \$@ | awk -F'--batch=' '{print \$2}' | cut -f 1 -d \" \"\`
cat tensorrt-output > \$LOG_FILE
echo \"batch size was \$BATCH_SIZE\" >> \$LOG_FILE

RESULT=\`cat tensorrt-output | tail -n2 | awk -F\" \" '{print \$6}'\`
echo \"result was \$RESULT\" >> \$LOG_FILE
RESULT=\`echo \"\$RESULT \$BATCH_SIZE\" | awk '{ val = ((1000 / \$1) * \$2); print val; }'\`
RESULT=\`echo \$RESULT | sed -e \"s/0 //\"\`
echo \"IMAGES PER SECOND RESULT: \$RESULT\" >> \$LOG_FILE" > tensorrt-inference
chmod +x tensorrt-inference
