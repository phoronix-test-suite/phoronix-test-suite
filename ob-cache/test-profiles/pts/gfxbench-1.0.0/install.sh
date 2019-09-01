#!/bin/sh

unzip -o GFXBench31_Linux.zip

echo "#!/bin/sh
cd GFXBench31_Linux/
LD_LIBRARY_PATH=bin/\$LD_LIBRARY_PATH ./bin/testfw_app \$@ > \$LOG_FILE 2>&1" > gfxbench
chmod +x gfxbench
