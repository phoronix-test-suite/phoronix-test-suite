#!/bin/sh

unzip -o NAMD_2.13_Win64-multicore.zip
unzip -o f1atpase.zip
sed -i 's/\/usr\/tmp/\/g' f1atpase/f1atpase.namd

echo "You may need to manually install Microsoft Visual C++ 2010 Service Pack 1 Redistributable Package if not already done so for this test to run: https://www.microsoft.com/en-us/download/confirmation.aspx?id=26999" > ~/install-message

cd ~
echo "#!/bin/sh
cd NAMD_2.13_Win64-multicore
./namd2.exe +p\$NUM_CPU_CORES +setcpuaffinity ../f1atpase/f1atpase.namd > \$LOG_FILE 2>&1" > namd
chmod +x namd
