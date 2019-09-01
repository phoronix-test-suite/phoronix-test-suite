#!/bin/sh

unzip -o Prime-Benchmark-20181001.zip
cd Prime-Benchmark-master/

RUSTFLAGS="-C target-cpu=native" rustc rust/main.rs -C opt-level=3 -o prime_rust
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd Prime-Benchmark-master/
./prime_rust 200000000 \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > rust-prime
chmod +x rust-prime
