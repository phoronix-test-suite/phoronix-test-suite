#!/bin/sh

tar -xf game_textures_1.tar.xz
tar -xf etcpak-1.0.tar.gz

tar -xf tracy-0.8.1.tar.gz
mv tracy-0.8.1/* etcpak-1.0/tracy

cd etcpak-1.0/unix
sed -i 's/NumTasks = 9/NumTasks = 200/g' ../Application.cpp 
make release
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd etcpak-1.0/unix
./etcpak \$@ ~/8k_game_textures.png > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > etcpak
chmod +x etcpak
