#!/bin/sh
echo "#!/bin/sh
cd otp-OTP-25.0.4
make -j \$NUM_CPU_CORES
echo \$? > ~/test-exit-status" > build-erlang
chmod +x build-erlang
