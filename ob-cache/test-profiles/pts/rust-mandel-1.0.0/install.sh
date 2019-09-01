#!/bin/sh

unzip -o mandel-rust-20181001.zip
cd mandel-rust-master/

RUSTFLAGS="-C target-cpu=native" cargo build --release
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd mandel-rust-master/
./target/release/mandel --bench --num_threads \$NUM_CPU_CORES --num_of_runs=1 --img_size 4096 --no_ppm > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > rust-mandel
chmod +x rust-mandel
