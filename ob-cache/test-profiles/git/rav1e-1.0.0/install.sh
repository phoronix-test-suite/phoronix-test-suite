#!/bin/sh

rm -rf rav1e-master
git clone https://github.com/xiph/rav1e.git rav1e-master
cargo build --release
echo $? > ~/install-exit-status
cd ~

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

cd rav1e-master
cargo build --bin rav1e --release -j $NUM_CPU_PHYSICAL_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
./rav1e-master/target/release/rav1e ./Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m --threads \$NUM_CPU_CORES --tiles 4 --output /dev/null \$@ > log.out 2>&1
echo \$? > ~/test-exit-status
tr -s '\r' '\n' < log.out > \$LOG_FILE" > rav1e
chmod +x rav1e
