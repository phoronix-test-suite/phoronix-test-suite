#!/usr/bin/env bash

## This requires the Nix package manager to function,
## both for the dependencies, and for the benchmark itself.
##
## Nix installation instructions:
##
##  https://nixos.org/download.html
##
## Once complete, this will set up the system-global /nix/store,
## which will cache the full set of benchmark dependencies,
## which should take under one gigabyte.

 set -xe 

tar -xf clash-benchmark-compilation-2.tar.xz

curl -L https://nixos.org/nix/install | sh
. $HOME/.nix-profile/etc/profile.d/nix.sh

cat > build-clash <<EOF
#!/usr/bin/env bash

. \$HOME/.nix-profile/etc/profile.d/nix.sh

cd benchmark-compilation

options=(
  --iterations 1
  --cores      \$NUM_CPU_CORES
)

{ ./bench/bench.sh "\${options[@]}" measure 2>&1
echo \$? > ~/test-exit-status
  echo \$? > ~/test-exit-status
} | tee test.log

EOF

chmod +x build-clash

## Fill the Nix store.
cd benchmark-compilation
./bench/bench.sh prepare 2>&1
