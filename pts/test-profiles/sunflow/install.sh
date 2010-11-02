#!/bin/sh

unzip -o sunflow-bin-v0.07.2.zip

echo "#!/bin/sh
cd sunflow

if [ \$SYS_MEMORY -ge 1280 ]
then
	JAVA_VM_MEMORY=1024M
elif [ \$SYS_MEMORY -ge 768 ]
then
	JAVA_VM_MEMORY=512M
else
	JAVA_VM_MEMORY=256M
fi

java -server -Xmx\$JAVA_VM_MEMORY -jar sunflow.jar -bench > \$LOG_FILE 2>&1" > sunflow-benchmark
chmod +x sunflow-benchmark
