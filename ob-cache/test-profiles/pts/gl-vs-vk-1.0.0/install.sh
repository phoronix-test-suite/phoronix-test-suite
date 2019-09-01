#!/bin/sh

tar -xjvf GL_vs_VK-git-20170605.tar.bz2
cd GL_vs_VK-git-20170605/
git submodule update --init

mkdir build
cd build
cmake ..
make -j $NUM_CPU_JOBS

cd ~
echo "#!/bin/sh
cd GL_vs_VK-git-20170605/bin
./GL_vs_VK \$@ > \$LOG_FILE" > gl-vs-vk
chmod +x gl-vs-vk
