#!/bin/sh
unzip -o OSPRayStudio-Room-Scene-3.zip
unzip -o ospray_studio-1.0.0.x86_64.windows.zip
echo "#!/bin/sh
cd OSPRayStudio-Room-Scene/
../ospray_studio-1.0.0.x86_64.windows/bin/ospStudio.exe benchmark --denoiser --format jpg \$@ RoomScene.sg > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray-studio
chmod +x ospray-studio
