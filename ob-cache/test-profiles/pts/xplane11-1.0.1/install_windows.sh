#!/bin/sh

steam steam://install/269950
cd "C:\Program Files (x86)\Steam\steamapps\common\X-Plane 11"
echo "269950" > steam_appid.txt

cd ~

echo "#!/bin/sh
cd \"C:\Program Files (x86)\Steam\steamapps\common\X-Plane 11\"
./X-Plane.exe \$@
mv Log.txt \$LOG_FILE" > xplane11
chmod +x xplane11
