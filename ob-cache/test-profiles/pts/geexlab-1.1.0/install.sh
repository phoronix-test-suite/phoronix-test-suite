#!/bin/sh

unzip -o GeeXLab_FREE_0.28.0.0_linux64.zip

echo "#!/bin/sh
cd GeeXLab_FREE_linux64/
rm -f _geexlab_log.txt
./GeeXLab \$@ 
cat _geexlab_log.txt > \$LOG_FILE" > geexlab
chmod +x geexlab

