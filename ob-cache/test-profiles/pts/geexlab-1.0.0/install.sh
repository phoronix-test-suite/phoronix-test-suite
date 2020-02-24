#!/bin/sh

unzip -o GeeXLab_FREE_linux64_0.25.3.0.zip

echo "#!/bin/sh
cd GeeXLab_FREE_linux64/
rm -f _geexlab_log.txt
./GeeXLab \$@ 
cat _geexlab_log.txt > \$LOG_FILE" > geexlab
chmod +x geexlab

