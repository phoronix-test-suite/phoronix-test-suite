#!/bin/sh
tar -xf mlc_v3.10.tgz
echo "#!/bin/bash
cd Linux/
HAS_AVX512=\"\"
if grep avx512 /proc/cpuinfo > /dev/null
then
	HAS_AVX512=\"-Z \"
fi
./mlc \$HAS_AVX512 \$@ > \$LOG_FILE" > intel-mlc
chmod +x intel-mlc
