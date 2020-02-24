#!/bin/sh

chmod +x vray-benchmark-4.10.07

echo "#!/bin/sh
./vray-benchmark-4.10.07 \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > v-ray
chmod +x v-ray

