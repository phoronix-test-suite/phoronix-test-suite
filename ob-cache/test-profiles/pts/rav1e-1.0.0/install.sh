#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z

tar -xf rav1e-20191023.tar.gz
cd rav1e-20191023
cargo build --bin rav1e --release -j $NUM_CPU_PHYSICAL_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd rav1e-20191023
./target/release/rav1e ../Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m --threads \$NUM_CPU_CORES --tile-rows 4 --tile-cols 4 --output /dev/null --limit 60 > log.out 2>&1
echo \$? > ~/test-exit-status

tr -s '\r' '\n' < log.out > \$LOG_FILE" > rav1e
chmod +x rav1e
