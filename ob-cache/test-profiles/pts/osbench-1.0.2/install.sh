#!/bin/sh

unzip -o osbench-master-1.zip
cd osbench-master
mkdir out
cd out
meson --buildtype=release ../src
ninja
mkdir target
echo $? > ~/install-exit-status

# so same names as Windows binaries for easier cross-platform...
mv create_files create_files.exe
mv create_processes create_processes.exe
mv create_threads create_threads.exe
mv launch_programs launch_programs.exe
mv mem_alloc mem_alloc.exe

cd ~
echo "#!/bin/sh
cd osbench-master/out/
./\$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > osbench
chmod +x osbench
