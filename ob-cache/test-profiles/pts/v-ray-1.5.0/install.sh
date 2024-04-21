#!/bin/sh
chmod +x vray-benchmark-6.00.00
echo "#!/bin/sh
echo y | ./vray-benchmark-6.00.00 \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > v-ray
chmod +x v-ray
