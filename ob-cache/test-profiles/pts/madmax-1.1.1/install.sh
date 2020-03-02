#!/bin/sh

tar -xjvf madmax-prefs-2.tar.bz2
cat > preferences <<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<registry>
    <key name="HKEY_CURRENT_USER">
        <key name="Software">
            <key name="Feral Interactive">
                <key name="Mad Max">
                    <key name="Setup">
                        <value name="AdjustResolution" type="integer">1</value>
                        <value name="AllowPausing" type="integer">0</value>
                        <value name="DefaultPreset" type="integer">0</value>
                        <value name="DefaultVSync" type="integer">0</value>
                        <value name="FullScreen" type="integer">1</value>
                        <value name="GameOptionsDialogLastTab" type="integer">20000</value>
                        <value name="GameOptionsDialogShouldShow" type="integer">0</value>
                        <value name="GameOptionsDialogShouldShowBigPicture" type="integer">0</value>
                        <value name="GameOptionsDialogShown" type="integer">1</value>
                        <value name="PauseMoviesOnPause" type="integer">0</value>
                        <value name="PauseOnSuspend" type="integer">0</value>
                        <value name="PauseSoundOnPause" type="integer">0</value>
                        <value name="PauseTimersOnPause" type="integer">0</value>
                        <value name="ScreenH" type="integer">2160</value>
                        <value name="ScreenW" type="integer">3840</value>
                        <value name="SeenSpecificationAlertUUIDDefaultHardwareSpecificationsClass" type="integer">1</value>
                        <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                        <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
                    </key>
                </key>
            </key>
            <key name="IndirectX">
                <key name="Direct3D">
                    <key name="Config">
                        <value name="UseVulkan" type="integer">1</value>
                    </key>
                </key>
            </key>
            <key name="MacDoze">
                <key name="Config">
                    <value name="ExtraCommandLine" type="string">--feral-benchmark</value>
                    <value name="ExtraCommandLineEnabled" type="integer">1</value>
                </key>
            </key>
        </key>
    </key>
</registry>
EOT

HOME=$DEBUG_REAL_HOME steam steam://install/234140
mkdir -p $DEBUG_REAL_HOME/.local/share/feral-interactive/Mad\ Max


echo "#!/bin/bash
ORIG_HOME=\$HOME
. steam-env-vars.sh
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Mad Max/VFS/User/AppData/Roaming/WB Games/Mad\ Max/FeralBenchmark/*
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Mad Max/VFS/User/AppData/Roaming/WB Games/Mad\ Max/settings.ini
cp -f preferences \$DEBUG_REAL_HOME/.local/share/feral-interactive/Mad\ Max/preferences
cd \$DEBUG_REAL_HOME/.local/share/feral-interactive/Mad\ Max
sed -ie \"s/3840/\$1/g\" preferences
sed -ie \"s/2160/\$2/g\" preferences

sed -i  's/<value name=\"DefaultPreset\" type=\"integer\">0<\/value>/<value name=\"DefaultPreset\" type=\"integer\">\$3<\/value>/' preferences

if [ \"X\$4\" = \"XOPENGL\" ]
then
	sed -i  's/<value name=\"UseVulkan\" type=\"integer\">1<\/value>/<value name=\"UseVulkan\" type=\"integer\">0<\/value>/' preferences
	
fi

cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Mad\ Max/bin
./MadMax --feral-benchmark
\$PHP_BIN \$ORIG_HOME/mad-max-parser.php > \$LOG_FILE
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Mad Max/VFS/User/AppData/Roaming/WB Games/Mad\ Max/FeralBenchmark/*" > madmax
chmod +x madmax
