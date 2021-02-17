#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/65980
echo $? > ~/install-exit-status

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Sid\ Meier\'s\ Civilization\ Beyond\ Earth/
rm -f \$DEBUG_REAL_HOME/.local/share/aspyr-media/Sid\ Meier\'s\ Civilization\ Beyond\ Earth/Logs/lategameview

echo \"[General]
Version = 1

[GraphicsSettings]
OverlayLevel = 2
ShadowLevel = 2
ReflectionLevel = 0
FOWLevel = 1
TerrainWaterQuality = 0
VFXQuality = 1
FadeShadows = 0
Enable Constant Rebasing = 1
Enable Threaded Rendering = 1
Enable Bloom = 1
Enable UIBlur = 1
Enable DoF = 1
Version = 3
Enable MGPU = 0

[ScreenSettings]
MSAASamples = 1
WaitForVSync = 0
Refresh Rate = 0
WindowResX = \$1
WindowResY = \$2
FullScreen = 1
StereoConvergenceMin = 0.000000
StereoConvergenceMax = 0.000000
StereoCursorOffset = 0.000000
ScreenShotWidth = 0
ScreenShotHeight = 0
DisableAdvancedAAModes = 0
Version = 1

[TerrainSettings]
TerrainDetailLevel = 2
TerrainTessLevel = 2
TerrainShadowQuality = 3
TerrainPageinSpeedStill = 6
TerrainPageinSpeedMoving = 3
BlockOnLoad = 0
AutoUpdateCells = 1
Version = 1

[LeaderheadSettings]
LeaderTextureReduction = 0
LeaderTextureBackgroundLoad = 0
AllowSM41 = 1
AllowSM50 = 1
AllowLeaderAA = 1
EnableShadows = 1
EnableSoftShadows = 1
CubeShadowResolution = 1024
MaxResidentScenes = -1
LeaderQuality = 3
SkinQuality = 2
SkinResolution = 1024
AspectAdjust = 0.000000
TargetAspect = 1.777778
EnableBloom = 1
EnableDistortion = 1
UseScreenShots = 0
UseGPUDecompress = 1
Version = 1
\" > \$DEBUG_REAL_HOME/.local/share/aspyr-media/Sid\ Meier\'s\ Civilization\ Beyond\ Earth/GraphicsSettings.ini

HOME=\$DEBUG_REAL_HOME LD_LIBRARY_PATH=\$DEBUG_REAL_HOME/.steam/ubuntu12_32:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/lib/i386-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/lib:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/usr/lib/i386-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/usr/lib:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/lib/x86_64-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/lib:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/lib/x86_64-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/lib::/usr/lib32:\$DEBUG_REAL_HOME/.steam/ubuntu12_32:\$DEBUG_REAL_HOME/.steam/ubuntu12_64:\$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Sid\ Meier\'s\ Civilization\ Beyond\ Earth:\$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Sid\ Meier\'s\ Civilization\ Beyond\ Earth/bin STEAM_CLIENT_CONFIG_FILE=\$DEBUG_REAL_HOME/.steam/steam.cfg SteamGameId=65980 STEAM_RUNTIME=\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime PATH=\$DEBUG_REAL_HOME/.steam/ubuntu12_32:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/bin:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games SteamAppId=65980 SteamTenfoot=0 ./CivBE -benchmark lategameview

cat \$DEBUG_REAL_HOME/.local/share/aspyr-media/Sid\ Meier\'s\ Civilization\ Beyond\ Earth/Logs/lategameview > \$LOG_FILE" > civbe
chmod +x civbe
