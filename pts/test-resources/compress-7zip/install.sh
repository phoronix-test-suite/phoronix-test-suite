#!/bin/sh

tar -xjf p7zip_4.58_src_all.tar.bz2
cd p7zip_4.58/
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
./p7zip_4.58/bin/7za b" > compress-7zip
chmod +x compress-7zip
