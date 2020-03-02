#!/bin/bash

if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi

HOME=$DEBUG_REAL_HOME steam steam://install/507490

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/507490/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/Ashes\ of\ the\ Singularity\ -\ Escalation/Output*.txt

echo \"
[General]
IniVersion=23

[System]
FullScreen=0
Resolution=\$1,\$2
Api=dx11
VSync=0
HotLoadEnabled=0
UIScale=1.0
CameraPanSpeed=1.0
BindCursor=Always
CursorScale=0
AutoBenchRun[Off,GPUFocused,CPUFocused]=Off
CameraPanKeys=Arrows
AFRGPU=0
EnvFX=1
Clouds=1
EnvMap=0
Noise=0
AsymetricGPU=0
SkipMovie=1
UploadReplay=1
AutoSave=1
HealthBarsAlways=0
DisableIntermediateMode=0
SteamAvatars=1
CameraPanAlt=0
ForceStop=0
AutoLevelT3=0
QuickArmyAttach=0
EmulateFullscreen=0
AsyncComputeOff=0
AllowHooks=0
BenchmarkUpload=1
HDRBackBuffer=0
HDRScale=1.000000
HDRSplitScreen=0
HDRSplitScreenNITS=100.000000

[Quality]
PointLights=Off
Glare=Off
TerrainDetailObjLevel=Off
ShadingSamples=4
TerrainShadingSamples=4
ShadowQuality=Low
MaxMotionBlurFrameTime=0
MotionBlurInterframeTime=0
MSAASamples=1
MipsToRemove=2
RenderResolution=\$1,\$2
HalfResTerrain=1

[Audio]
MuteAll=0
Volume_Master=100
Volume_Music=50
Volume_Environmental=50
Volume_Effect=50
Volume_UI=50
Volume_Voice=50

[SPGameSettings]
NumHumanPlayers=1
HideTerrain=0
BuildExtractors=1
EntrenchmentBonus=0
CrustMetal=0
CoreRadiance=0
QuantumCoherence=0
NanobotProductivity=0
CreepLevel=0
MantleThickness=0
AtmosphericDensity=0
JuggernautEnabled=1
SolarWeather=0
SupplyLine=0
VictoryCap=-1
DefaultMap=Frosthaven
[SPGameSettings_Player0]
IsObserver=0
ColorIndex=1
StartingPosIndex=-1
TeamIndex=255
Faction=0
Difficulty=0
ResourceModifier=1.000000
PersonalityHash=0
IsAI=0
IsLocalPlayer=1
[SPGameSettings_Player1]
IsObserver=0
ColorIndex=2
StartingPosIndex=-1
TeamIndex=255
Faction=1
Difficulty=0
ResourceModifier=1.000000
PersonalityHash=0
IsAI=1
IsLocalPlayer=0

[MPGameSettings]
NumHumanPlayers=2
HideTerrain=0
BuildExtractors=1
EntrenchmentBonus=0
CrustMetal=0
CoreRadiance=0
QuantumCoherence=0
NanobotProductivity=0
CreepLevel=0
MantleThickness=0
AtmosphericDensity=0
JuggernautEnabled=1
SolarWeather=0
SupplyLine=0
VictoryCap=-1
DefaultMap=Frosthaven
[MPGameSettings_Player0]
IsObserver=0
ColorIndex=1
StartingPosIndex=-1
TeamIndex=255
Faction=0
Difficulty=0
ResourceModifier=1.000000
PersonalityHash=0
IsAI=0
IsLocalPlayer=1
[MPGameSettings_Player1]
IsObserver=0
ColorIndex=2
StartingPosIndex=-1
TeamIndex=255
Faction=1
Difficulty=0
ResourceModifier=1.000000
PersonalityHash=0
IsAI=1
IsLocalPlayer=0\" > \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/507490/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/Ashes\ of\ the\ Singularity\ -\ Escalation/settings.ini

PROTON_NO_ESYNC=1 HOME=\$DEBUG_REAL_HOME steam -applaunch 507490  -nolauncher -benchmark benchfinal
sleep 30
while pgrep -x \"AshesEscalation\" > /dev/null; do
    sleep 2
done
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/507490/pfx/drive_c/users/steamuser/My\ Documents/My\ Games/Ashes\ of\ the\ Singularity\ -\ Escalation/Output*.txt > \$LOG_FILE" > ashes-escalation
chmod +x ashes-escalation
