#!/bin/sh

tar -xf wasmer-1.0.2.tar.gz
cd wasmer-1.0.2

# First build to get dependencies and test
cargo build --release --manifest-path lib/cli/Cargo.toml --features cranelift,singlepass --bin wasmer
echo $? > ~/install-exit-status
cargo clean

cd ~
echo "#!/bin/sh
cd wasmer-1.0.2
cargo build --release --manifest-path lib/cli/Cargo.toml --features cranelift,singlepass --bin wasmer
echo \$? > ~/test-exit-status" > build-wasmer

chmod +x build-wasmer
