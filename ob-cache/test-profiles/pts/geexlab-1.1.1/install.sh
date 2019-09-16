#!/bin/sh

unzip -o GeeXLab_FREE_0.28.0.0_linux64.zip

cd GeeXLab_FREE_linux64
echo "#!/bin/sh
exit" > fake-browser
chmod +x fake-browser
ln -s fake-browser xdg-open
# workaround update version check

cd ~
echo "#!/bin/sh
cd GeeXLab_FREE_linux64/
rm -f _geexlab_log.txt
PATH=.:\$PATH ./GeeXLab \$@ 
cat _geexlab_log.txt > \$LOG_FILE" > geexlab
chmod +x geexlab

