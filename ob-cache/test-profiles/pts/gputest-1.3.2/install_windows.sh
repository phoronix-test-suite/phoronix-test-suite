#!/bin/sh

unzip -o GpuTest_Windows_x64_0.7.0.zip

echo "#!/bin/sh
cd GpuTest_Windows_x64_0.7.0
rm -f _geeks3d_gputest_log.txt
./GpuTest.exe \$@ 
cat _geeks3d_gputest_log.txt > \$LOG_FILE" > gputest
chmod +x gputest
