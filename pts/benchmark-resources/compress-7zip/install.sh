#!/bin/sh

cd $1

tar -xjf p7zip_4.57_src_all.tar.bz2
cd p7zip_4.57/
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
./p7zip_4.57/bin/7za b" > compress-7zip
chmod +x compress-7zip
