#!/bin/sh

tar -xJf tesseract_2014_05_12_first_edition_linux.tar.xz 
tar -xf tess_bench.tar.gz

mv bench.so tesseract
mv bench.dmo tesseract
mv tesseract tesseract_game

echo "#!/bin/sh
cd tesseract_game/

rm ~/.tesseract/config/saved.cfg
./tesseract_unix -x'maxfps = 0; benchsamples = 0; benchfps = 0; getbenchsample = [benchsamples = (+f \$benchsamples 1); benchfps = (+f \$benchfps (getfps 1)); sleep 100 [getbenchsample]]; mapstart = [sleep 1 [follow 0]; sleep 100 [getbenchsample]]; demoend = [echo FPS: (divf \$benchfps \$benchsamples); mapstart = ""; demoend = ""; quit]; demo bench' \$@ > \$LOG_FILE 2>&1" > tesseract
chmod +x tesseract
