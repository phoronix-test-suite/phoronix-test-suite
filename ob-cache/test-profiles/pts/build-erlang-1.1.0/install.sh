#!/bin/sh

echo "#!/bin/sh
cd otp-OTP-23.2.6/
make -j \$NUM_CPU_CORES
echo \$? > ~/test-exit-status" > build-erlang

chmod +x build-erlang
