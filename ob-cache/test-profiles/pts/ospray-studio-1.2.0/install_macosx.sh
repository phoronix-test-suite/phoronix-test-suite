#!/bin/sh
unzip -o OSPRayStudio-Room-Scene-2.zip
unzip -o ospray_studio-0.13.0.x86_64.macosx.zip
echo "--- OSPRayStudio-Room-Scene/RoomScene.sg.orig	2022-06-29 19:15:28.000000000 -0400
+++ OSPRayStudio-Room-Scene/RoomScene.sg	2023-10-28 13:59:40.020080317 -0400
@@ -564,7 +564,7 @@
                                                     {
                                                         \"name\": \"stereoMode\",
                                                         \"type\": \"PARAMETER\",
-                                                        \"subType\": \"int\",
+                                                        \"subType\": \"OSPStereoMode\",
                                                         \"sgOnly\": false,
                                                         \"value\": 0,
                                                         \"minMax\": [
@@ -947,7 +947,7 @@
                                                     {
                                                         \"name\": \"stereoMode\",
                                                         \"type\": \"PARAMETER\",
-                                                        \"subType\": \"int\",
+                                                        \"subType\": \"OSPStereoMode\",
                                                         \"sgOnly\": false,
                                                         \"value\": 0,
                                                         \"minMax\": [
@@ -1330,7 +1330,7 @@
                                                     {
                                                         \"name\": \"stereoMode\",
                                                         \"type\": \"PARAMETER\",
-                                                        \"subType\": \"int\",
+                                                        \"subType\": \"OSPStereoMode\",
                                                         \"sgOnly\": false,
                                                         \"value\": 0,
                                                         \"minMax\": [
" > fix-sg.patch
patch -p0 < fix-sg.patch
echo "#!/bin/sh
export PATH=\$HOME/ospray_studio-0.13.0.x86_64.macosx/bin/:\$PATH
cd OSPRayStudio-Room-Scene/
ospStudio benchmark --denoiser --format jpg \$@ RoomScene.sg > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ospray-studio
chmod +x ospray-studio
