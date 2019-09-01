#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/289070

echo "#!/bin/bash

. steam-env-vars.sh

cd \$DEBUG_REAL_HOME/.local/share/aspyr-media/Sid\ Meier\'s\ Civilization\ VI/

echo \";Altering this number will cause the app to overwrite this file with defaults
Version 15

[Language]
;The previous language set by steam.
SteamLanguage english

;The language to use for all text in-game.
DisplayLanguage en_US

;The language to use for all audio in-game.
AudioLanguage English(US)

;Set to 1 to enable subtitles.
EnableSubtitles 0

;Enable language fall-back.
EnableFallback 1

[Video]
;Device ID of the GPU device to use (as provided by the OS).
; DeviceID 7040

;Render width in pixels.
RenderWidth \$2

;Render height in pixels.
RenderHeight \$3

;0 : windowed, 1 : fullscreen, 2 : windowed with no title bar
FullScreen 1

;UI will be scaled by 1 plus this value, non-integers may cause small artifacts
UIUpscale 0.000000

;Set to 1 to allow the use of software renderers such as Microsoft's WARP
AllowSWRenderer 0

;Set to 1 play the intro video on startup.
PlayIntroVideo 0

;0 : never grab, 1 : only in full screen, 2 : always grab
MouseGrab 0

[Performance]
;Number of engine job threads (including main thread). Use -1 to let game decide
MaxJobThreads -1

;Frame limiter. Tick app every N ms. 0 means tick continuously.
TickIntervalInMS 0

;Tick every N ms during game start. 0 means tick continuously.
LoadGameTickIntervalInMS 16

;Tick every N ms while the app is inactive.  0 means tick continuously.
InactiveTickIntervalInMS 32

;Should we throttle the game when its not the foreground app?
ThrottleWhileInactive 0

[Debug]
;Enable FireTuner.
EnableTuner 0

;Enable Debug menu.
EnableDebugMenu 0

;Set to 0 to disable audio.
EnableAudio 1

;Enable MemoryTracker.
EnableMemoryTracker 0

;Enable Debug information in the plot info tooltips.
EnableDebugPlotInfo 0

;Enable Automatic Bug Collection.
EnableBugCollection 1

;Enable Log Collection with Bugs.
EnableLogCollection 0

;Enable Data Error Collection.
EnableDataErrorCollection 0

;Enable Local Build Bug Collection.
EnableLocalBuildCollection 0

;Enable ArtManager loading in the background.
EnableBackgroundArtLoading 1

;Enable assertions.
EnableAsserts 1

;Forces the game to load only that save file.
PlayNowSave 

;Log all game core events.
EnableGameCoreEventLog 0

;End game state.
EndGameState 

;Tutorial state.
TutorialState 

[Misc]
;User has accepted the unknown graphics device pop up.
AcceptedUnknownDevice 0

;User has accepted the outdated driver pop up.
AcceptedOutdatedDriver 0

;
TelemetryUploadNecessary 1

[UI]
;Is the touch screen support enabled?
IsTouchScreenEnabled 0
\" > AppOptions.txt

if [ \"\$1\" == \"LOW\" ]
then

echo \"
;Altering this number will cause the app to overwrite this file with defaults
Version 10

[Video]
;Overrides all other options to achieve desired performance profile. Must be set to -1 for changes in this file to be applied.
PerformanceImpact 0

;Overrides all other options to achieve desired memory profile. Must be set to -1 for changes in this file to be applied.
MemoryImpact 0

;MSAA sample count.  0 ==> 1. -1 ==> Max available
MSAA 1

;MSAA quality, enables EQAA or CSAA if supported
MSAAQuality 0

;1 = Wait for vertical sync. 0 = I don't mind tearing artifacts
VSync 0

;Resolution of overlay texture (NxN)
OverlayResolution 2048

;Resolution of shadow map (NxN)
ShadowMapResolution 2048

;Resolution of ambient occlusion depth map (NxN)
AODepthResolution 1024

;Resolution of ambient occlusion render texture (NxN)
AORenderResolution 1024

;Resolution of mask for FOW (NxN)
TerrainHeightMaskResolution 1024

;Refresh rate for fullscreen mode. Ignored for windowed mode
RefreshRateInHz 59

;Set to 0 to use full resolution textures.  Non-zero to reduce them
ReducedAssetTextures 1

[Terrain]
;1 = full-res, 2 = low-res.
TerrainSynthesisDetailLevel 2

;Valid settings are 0 to 4.  Higher number = higher quality.
TerrainQuality 0

;Discard less important terrain materials. Saves memory.
ReducedTerrainMaterials 1

;Set to use low quality terrain shader (reduces texture filtering quality and specular lighting)
LowQualityTerrainShader 1

[General]
;Number of passes when calculating screen-space reflection with 16 samples per pass, default is 4 (64 samples). 0 disables SSR.
SSReflectPasses 0

;Drop highest mip level for water LEAN maps
UseLowResWater 1

;Disable high quality water (no refraction or reflection).
UseLowQualityWaterShader 1

;Default time of day
DefaultTimeOfDay 11.500000

;Enable/disable ambient time of day cycling.
AmbientTimeOfDay 0

;Set which game views are enabled: 0 = Strategic only. 1 = 3D only. 2 = Both.
AvailableViews 2

;Indicates the level of detail for vfx. 0 = low. 1 = high
VFXDetailLevel 0

;How much stuff on the map? 0 = not a lot. 1 = a lot
ClutterDetailLevel 0

;Enable screen-space overlay effect.
ScreenSpaceOverlay 0

[AO]
;Whether or not to turn on AO
EnableAO 0

[Bloom]
;Whether or not to turn on Bloom
EnableBloom 0

[Shadows]
;Whether or not to turn on Shadows
EnableShadows 0

[DynamicLighting]
;Whether or not to use dynamic lighting
EnableDynamicLighting 0

[Leaders]
;Overall leader rendering quality
Quality 0

[Video]
;Submit draws via the DX11 immediate context
DX11_ForceImmediate 1

[DX12]
;Throw away all compute dispatches.  This is a debugging switch
DiscardCompute 0

;Force d3d debug layer.  This is a debugging switch
ForceDebugLayer 0

;If supported, use Root Signature 1.1 (1), or don't (0), or platform default(-1)
EnableRootSig_1_1 -1

;Set DX12 compute queue usage on (1), off (0), or platform default(-1).
EnableAsyncCompute -1

;Enable DX12 split-screen optimizations for multi-GPU systems. On (1), Off(0), or platform default (-1) [Platform Default is OFF, but may change in the future.]
EnableSplitScreenMultiGPU 0

[Debug]
;Set to 1 to drop all rendering except for UI.  This is a debugging switch
UIOnlyRendering 0

\" > GraphicsOptions.txt

elif [ \"\$1\" == \"HIGH\" ]
then

echo \";Altering this number will cause the app to overwrite this file with defaults
Version 10

[Video]
;Overrides all other options to achieve desired performance profile. Must be set to -1 for changes in this file to be applied.
PerformanceImpact -1

;Overrides all other options to achieve desired memory profile. Must be set to -1 for changes in this file to be applied.
MemoryImpact -1

;MSAA sample count.  0 ==> 1. -1 ==> Max available
MSAA 4

;MSAA quality, enables EQAA or CSAA if supported
MSAAQuality 0

;1 = Wait for vertical sync. 0 = I don't mind tearing artifacts
VSync 0

;Resolution of overlay texture (NxN)
OverlayResolution 2048

;Resolution of shadow map (NxN)
ShadowMapResolution 2048

;Resolution of ambient occlusion depth map (NxN)
AODepthResolution 2048

;Resolution of ambient occlusion render texture (NxN)
AORenderResolution 2048

;Resolution of mask for FOW (NxN)
TerrainHeightMaskResolution 1024

;Refresh rate for fullscreen mode. Ignored for windowed mode
RefreshRateInHz 59

;Set to 0 to use full resolution textures.  Non-zero to reduce them
ReducedAssetTextures 0

[Terrain]
;1 = full-res, 2 = low-res.
TerrainSynthesisDetailLevel 1

;Valid settings are 0 to 4.  Higher number = higher quality.
TerrainQuality 4

;Discard less important terrain materials. Saves memory.
ReducedTerrainMaterials 0

;Set to use low quality terrain shader (reduces texture filtering quality and specular lighting)
LowQualityTerrainShader 0

[General]
;Number of passes when calculating screen-space reflection with 16 samples per pass, default is 4 (64 samples). 0 disables SSR.
SSReflectPasses 1

;Drop highest mip level for water LEAN maps
UseLowResWater 0

;Disable high quality water (no refraction or reflection).
UseLowQualityWaterShader 0

;Default time of day
DefaultTimeOfDay 11.500000

;Enable/disable ambient time of day cycling.
AmbientTimeOfDay 0

;Set which game views are enabled: 0 = Strategic only. 1 = 3D only. 2 = Both.
AvailableViews 2

;Indicates the level of detail for vfx. 0 = low. 1 = high
VFXDetailLevel 1

;How much stuff on the map? 0 = not a lot. 1 = a lot
ClutterDetailLevel 1

;Enable screen-space overlay effect.
ScreenSpaceOverlay 1

[AO]
;Whether or not to turn on AO
EnableAO 1

[Bloom]
;Whether or not to turn on Bloom
EnableBloom 1

[Shadows]
;Whether or not to turn on Shadows
EnableShadows 1

[DynamicLighting]
;Whether or not to use dynamic lighting
EnableDynamicLighting 1

[Leaders]
;Overall leader rendering quality
Quality 3

[Video]
;Submit draws via the DX11 immediate context
DX11_ForceImmediate 1

[DX12]
;Throw away all compute dispatches.  This is a debugging switch
DiscardCompute 0

;Force d3d debug layer.  This is a debugging switch
ForceDebugLayer 0

;If supported, use Root Signature 1.1 (1), or don't (0), or platform default(-1)
EnableRootSig_1_1 -1

;Set DX12 compute queue usage on (1), off (0), or platform default(-1).
EnableAsyncCompute -1

;Enable DX12 split-screen optimizations for multi-GPU systems. On (1), Off(0), or platform default (-1) [Platform Default is OFF, but may change in the future.]
EnableSplitScreenMultiGPU 0

[Debug]
;Set to 1 to drop all rendering except for UI.  This is a debugging switch
UIOnlyRendering 0
\" > GraphicsOptions.txt

else
exit
fi

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Sid\ Meier\'s\ Civilization\ VI/

rm -f \$DEBUG_REAL_HOME/.local/share/aspyr-media/Sid\ Meier\'s\ Civilization\ VI/Logs/Benchmark-*.csv

./Civ6 -benchmark

cd \$DEBUG_REAL_HOME/.local/share/aspyr-media/Sid\ Meier\'s\ Civilization\ VI/Logs/
cat Benchmark-*.csv > \$LOG_FILE
perl -lane '\$a+=\$_ for(@F);\$f+=scalar(@F);END{print \"Average FPS: \".1000/
(\$a/\$f)}' Benchmark-*.csv >> \$LOG_FILE 2>1" > civilization-vi
chmod +x civilization-vi
