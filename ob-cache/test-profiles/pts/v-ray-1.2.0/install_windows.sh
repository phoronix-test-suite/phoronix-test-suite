#!/bin/sh


echo "#!/bin/sh
./vray-benchmark-4.10.07-cli.exe \$@ > \$LOG_FILE" > v-ray
chmod +x v-ray

