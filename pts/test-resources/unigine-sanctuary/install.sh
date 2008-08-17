#!/bin/sh

tar -jxvf Unigine_Sanctuary2_Linux.tar.bz2

echo "#!/bin/sh
cd Unigine_Sanctuary2_Linux/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH
./bin/main_x86 \$@ 2>&1 | grep FPS" > unigine-sanctuary
chmod +x unigine-sanctuary

