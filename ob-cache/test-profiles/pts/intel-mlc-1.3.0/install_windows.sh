#!/bin/sh
tar -xf mlc_v3.12.tgz
echo "#!/bin/bash
cd Windows/
./mlc.exe \$@ > \$LOG_FILE" > intel-mlc
chmod +x intel-mlc
