#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf etcpak-0.7.tar.gz
tar -xf tracy-0.7.4.tar.gz

mv tracy-0.7.4/* etcpak-0.7/tracy

cd etcpak-0.7/unix
sed -i 's/NumTasks = 9/NumTasks = 200/g' ../Application.cpp 
make release
echo \$? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd etcpak-0.7/unix
./etcpak -b \$@ ~/sample-4.png > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > etcpak
chmod +x etcpak
