#!/bin/sh
7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z -aoa
tar -xf rav1e-0.6.1.tar.gz
cd rav1e-0.6.1
cargo build --bin rav1e --release -j $NUM_CPU_PHYSICAL_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd rav1e-0.6.1
./target/release/rav1e ../Bosphorus_1920x1080_120fps_420_8bit_YUV.y4m --threads \$NUM_CPU_CORES --tiles 16 --output /dev/null --benchmark \$@ > log.out 2>&1
echo \$? > ~/test-exit-status
tr -s '\r' '\n' < log.out > \$LOG_FILE" > rav1e
chmod +x rav1e
