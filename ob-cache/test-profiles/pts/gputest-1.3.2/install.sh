#!/bin/sh

unzip -o GpuTest_Linux_x64_0.7.0.zip
cd GpuTest_Linux_x64_0.7.0
chmod +x GpuTest

cd ~
echo "#!/bin/sh
cd GpuTest_Linux_x64_0.7.0
rm -f _geeks3d_gputest_log.txt
./GpuTest \$@ 
cat _geeks3d_gputest_log.txt > \$LOG_FILE" > gputest
chmod +x gputest
