#!/bin/sh

tar -xjf systester-1.1.0-src.tar.bz2
echo "20d19
<   unsigned long long hours = 0, minutes = 0, remainder = 0, total_seconds = 0;
21a21
>   seconds = (float) (stop - start) / 1000;
23,29c23
<   total_seconds = (unsigned long long) ((stop - start) / 1000);
<   hours = (unsigned long long) (total_seconds / 3600);
<   remainder = total_seconds % 3600;
<   minutes = (unsigned long long) (remainder / 60);
<   seconds = (float) ((stop - start) - (((hours * 3600) + (minutes * 60)) * 1000)) / 1000;
<   
<   sprintf (str, \"%2dh %2dm %6.3fs\", (int) hours, (int) minutes, seconds);
---
>   sprintf (str, \"%12.3fs\", seconds);" > OutTime.diff

patch systester-1.1.0-src/outtime.cpp OutTime.diff
make -j $NUM_CPU_JOBS -C systester-1.1.0-src/cli
echo $? > ~/install-exit-status
cp systester-1.1.0-src/cli/systester-cli systester-cli.bin
rm -r systester-1.1.0-sr

echo "#!/bin/sh
./systester-cli.bin -bench \$1 \$2 \$3 \$4> \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > systester-cli
chmod +x systester-cli
