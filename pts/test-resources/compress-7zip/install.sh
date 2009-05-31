#!/bin/sh

tar -xjf p7zip_4.65_src_all.tar.bz2
cd p7zip_4.65/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
rm -rf CPP
rm -rf check
rm -rf GUI
rm -rf DOCS
cd ..

echo "#!/bin/sh
./p7zip_4.65/bin/7za b > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > compress-7zip
chmod +x compress-7zip
