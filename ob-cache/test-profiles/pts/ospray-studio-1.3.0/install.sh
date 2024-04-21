#!/bin/sh
unzip -o OSPRayStudio-Room-Scene-3.zip
tar -xf ospray_studio-1.0.0.x86_64.linux.tar.gz
echo "#!/bin/sh
export PATH=\$HOME/ospray_studio-1.0.0.x86_64.linux/bin/:\$PATH
cd OSPRayStudio-Room-Scene/
ospStudio benchmark --denoiser --format jpg \$@ RoomScene.sg > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray-studio
chmod +x ospray-studio
