#!/bin/sh

chmod +x vraybench_1.0.8_lin_x64

echo "#!/bin/sh
./vraybench_1.0.8_lin_x64 \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > v-ray
chmod +x v-ray

