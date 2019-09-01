#!/bin/sh

7z x -y SPECjbb2015-1.02.iso -oSPECjbb2015
cd SPECjbb2015
chmod +x run_composite.bat

cd ~
echo "#!/bin/bash
cd SPECjbb2015
rm -f *-*-*/result/specjbb2015-C-*/report-*/specjbb2015-C-*.raw
./run_composite.sh
echo \$? > ~/test-exit-status

cat *-*-*/result/specjbb2015-C-*/report-*/specjbb2015-C-*.raw > \$LOG_FILE
" > spec-jbb2015
chmod +x spec-jbb2015
