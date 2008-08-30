#!/bin/sh

tar -jxvf Unigine_Islands_Linux.tar.bz2

echo "#!/bin/sh
cd Unigine_Islands_Linux/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/main_x86 \$@ 2>&1 | grep FPS" > unigine-islands
chmod +x unigine-islands

