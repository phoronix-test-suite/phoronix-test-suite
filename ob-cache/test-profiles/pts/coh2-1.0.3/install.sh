#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/231430

echo "#!/bin/bash
. steam-env-vars.sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Company\ of\ Heroes\ 2/bin

case \$3 in
\"MIN\")
	graphicsquality=0
	modeldetail=0
	effectsfidelity=0
	effectsdensity=0
	terraindetail=0
	snowdetail=1
	shadows=0
	ambocclquality=0
	shrubsdetail=0
	raindetail=0
	antialiasing=0
	reflections=0
	postprocessing=0
;;
\"LOW\")
	graphicsquality=1
	modeldetail=128
	effectsfidelity=0
	effectsdensity=0
	terraindetail=1
	snowdetail=1
	shadows=1
	ambocclquality=1
	shrubsdetail=0
	raindetail=1
	antialiasing=0
	reflections=0
	postprocessing=0
;;
\"MED\")
	graphicsquality=2
	modeldetail=256
	effectsfidelity=1
	effectsdensity=1
	terraindetail=1
	snowdetail=2
	shadows=2
	ambocclquality=1
	shrubsdetail=0
	raindetail=1
	antialiasing=0
	reflections=1
	postprocessing=1
;;
\"HIGH\")
	graphicsquality=3
	modeldetail=384
	effectsfidelity=2
	effectsdensity=2
	terraindetail=2
	snowdetail=2
	shadows=3
	ambocclquality=2
	shrubsdetail=1
	raindetail=2
	antialiasing=1
	reflections=1
	postprocessing=1
;;
\"MAX\")
	graphicsquality=5
	modeldetail=511
	effectsfidelity=3
	effectsdensity=3
	terraindetail=3
	snowdetail=3
	shadows=3
	ambocclquality=2
	shrubsdetail=1
	raindetail=2
	antialiasing=1
	reflections=1
	postprocessing=1
;;
esac
echo \"<?xml version=\\\"1.0\\\" encoding=\\\"UTF-8\\\"?>
<registry>
    <key name=\\\"HKEY_CLASSES_ROOT\\\">
    </key>
    <key name=\\\"HKEY_CURRENT_CONFIG\\\">
    </key>
    <key name=\\\"HKEY_CURRENT_USER\\\">
        <key name=\\\"Software\\\">
            <key name=\\\"IndirectX\\\">
                <key name=\\\"Direct3D\\\">
                    <key name=\\\"Config\\\">
                        <value name=\\\"AllowD3D1x\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"DisableSSOs\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"DisableUBOs\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"DXTCompressionSetting\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"FSAALevel\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"UseMTGL\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"UsePresentGammaCorrection\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"VBOMinMemToMap\\\" type=\\\"integer\\\">32768</value>
                    </key>
                </key>
            </key>
            <key name=\\\"MacDoze\\\">
                <key name=\\\"Config\\\">
                    <value name=\\\"GameCannotQuit\\\" type=\\\"integer\\\">1</value>
                    <value name=\\\"MouseModifierLeftAndRight\\\" type=\\\"integer\\\">0</value>
                    <value name=\\\"MouseModifierMiddle\\\" type=\\\"integer\\\">0</value>
                    <value name=\\\"MouseModifierRight\\\" type=\\\"integer\\\">0</value>
                    <value name=\\\"RemapCommandToControl\\\" type=\\\"integer\\\">1</value>
                </key>
            </key>
            <key name=\\\"Original Developer\\\">
                <key name=\\\"Company of Heroes 2\\\">
                    <key name=\\\"Config\\\">
                        <value name=\\\"advancedorders\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"ambocclquality\\\" type=\\\"integer\\\">\$ambocclquality</value>
                        <value name=\\\"antialiasing\\\" type=\\\"integer\\\">\$antialiasing</value>
                        <value name=\\\"classickeybindings\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"classicxpkickers\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"effectsdensity\\\" type=\\\"integer\\\">\$effectsdensity</value>
                        <value name=\\\"effectsfidelity\\\" type=\\\"integer\\\">\$effectsfidelity</value>
                        <value name=\\\"graphicsquality\\\" type=\\\"integer\\\">\$graphicsquality</value>
                        <value name=\\\"height\\\" type=\\\"integer\\\">\$1</value>
                        <value name=\\\"hudscale\\\" type=\\\"integer\\\">255</value>
                        <value name=\\\"keyscroll\\\" type=\\\"integer\\\">128</value>
                        <value name=\\\"mastervolume\\\" type=\\\"integer\\\">255</value>
                        <value name=\\\"modelanimationdetail\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"modeldetail\\\" type=\\\"integer\\\">\$modeldetail</value>
                        <value name=\\\"mousescroll\\\" type=\\\"integer\\\">128</value>
                        <value name=\\\"musicvolume\\\" type=\\\"integer\\\">255</value>
                        <value name=\\\"objectscarring\\\" type=\\\"integer\\\">-1</value>
                        <value name=\\\"physics\\\" type=\\\"integer\\\">3</value>
                        <value name=\\\"playercolour\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"postprocessing\\\" type=\\\"integer\\\">\$postprocessing</value>
                        <value name=\\\"raindetail\\\" type=\\\"integer\\\">\$raindetail</value>
                        <value name=\\\"reflections\\\" type=\\\"integer\\\">\$reflections</value>
                        <value name=\\\"refreshratedenominator\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"refreshratenumerator\\\" type=\\\"integer\\\">60</value>
                        <value name=\\\"screengamma\\\" type=\\\"integer\\\">3</value>
                        <value name=\\\"sfxvolume\\\" type=\\\"integer\\\">255</value>
                        <value name=\\\"shadows\\\" type=\\\"integer\\\">\$shadows</value>
                        <value name=\\\"showaestheticitems\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showcustomitems\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showglobalunitcontrol\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"shownoncriticaleventcues\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showpaths\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showsubtitles\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showunitdescription\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showunitocclusion\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"showxpkickers\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"shrubsdetail\\\" type=\\\"integer\\\">\$shrubsdetail</value>
                        <value name=\\\"snowdetail\\\" type=\\\"integer\\\">\$snowdetail</value>
                        <value name=\\\"soundconfig\\\" type=\\\"integer\\\">7</value>
                        <value name=\\\"soundfrequency\\\" type=\\\"integer\\\">44100</value>
                        <value name=\\\"soundquality\\\" type=\\\"integer\\\">2</value>
                        <value name=\\\"soundreverb\\\" type=\\\"integer\\\">2</value>
                        <value name=\\\"soundvoices\\\" type=\\\"integer\\\">128</value>
                        <value name=\\\"speechvolume\\\" type=\\\"integer\\\">255</value>
                        <value name=\\\"stickyselection\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"terraindetail\\\" type=\\\"integer\\\">\$terraindetail</value>
                        <value name=\\\"texturedetail\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"verticalsync\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"width\\\" type=\\\"integer\\\">\$2</value>
                        <value name=\\\"worldviewquality\\\" type=\\\"integer\\\">2</value>
                    </key>
                    <key name=\\\"Setup\\\">
                        <value name=\\\"BackingScaleFactor\\\" type=\\\"binary\\\">000000000000f03f</value>
                        <value name=\\\"DisableMomentumScrolling\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"ForceTestServer\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"FullScreen\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"GameOptionsDialogShouldShow\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"Language\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"LimitTextureDetailToMedium\\\" type=\\\"integer\\\">0</value>
                        <value name=\\\"QuitClosesWindow\\\" type=\\\"integer\\\">1</value>
                        <value name=\\\"ScreenD\\\" type=\\\"integer\\\">32</value>
                        <value name=\\\"ScreenH\\\" type=\\\"integer\\\">\$2</value>
                        <value name=\\\"ScreenR\\\" type=\\\"integer\\\">15360</value>
                        <value name=\\\"ScreenW\\\" type=\\\"integer\\\">\$1</value>
                    </key>
                </key>
            </key>
            <key name=\\\"SDL\\\">
                <key name=\\\"Config\\\">
                    <value name=\\\"DisableDispatchlessContext\\\" type=\\\"integer\\\">0</value>
                    <value name=\\\"EnableForceFeedback\\\" type=\\\"integer\\\">0</value>
                </key>
            </key>
        </key>
    </key>
    <key name=\\\"HKEY_LOCAL_MACHINE\\\">
        <key name=\\\"Software\\\">
        </key>
    </key>
    <key name=\\\"HKEY_USERS\\\">
    </key>
</registry>
\" > \$DEBUG_REAL_HOME/.local/share/feral-interactive/CompanyOfHeroes2/preferences

rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/CompanyOfHeroes2/AppData/LogFiles/pts.csv

HOME=\$DEBUG_REAL_HOME ./CompanyOfHeroes2 --show-performance -perftest pts.csv -autotest performance_test.lua
cp \$DEBUG_REAL_HOME/.local/share/feral-interactive/CompanyOfHeroes2/AppData/LogFiles/pts.csv \$LOG_FILE" > coh2
chmod +x coh2
