#!/bin/sh
unzip -o FurMark_2.1.0.2_linux64.zip
echo "#!/bin/sh
cd FurMark_linux64
./furmark \$@ > \$LOG_FILE 2>&1" > furmark
chmod +x furmark
