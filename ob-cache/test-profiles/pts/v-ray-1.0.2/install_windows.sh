#!/bin/sh


echo "#!/bin/sh
./vraybench_1.0.8_win_x64-cli.exe \$@ > \$LOG_FILE" > v-ray
chmod +x v-ray

