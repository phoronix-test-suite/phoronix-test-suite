#!/bin/sh
tar -xf wasmer-2.3.0.tar.gz
cd wasmer-2.3.0

# First build to get dependencies and test
cargo build --release --manifest-path lib/cli/Cargo.toml --features cranelift,singlepass --bin wasmer
echo $? > ~/install-exit-status
cargo clean

cd ~
echo "#!/bin/sh
cd wasmer-2.3.0
cargo build --release --manifest-path lib/cli/Cargo.toml --features cranelift,singlepass --bin wasmer
echo \$? > ~/test-exit-status" > build-wasmer
chmod +x build-wasmer
