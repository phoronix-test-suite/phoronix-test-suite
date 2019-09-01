#!/bin/sh
unzip -o SeriousSam2017-TFE-1.zip

HOME=$DEBUG_REAL_HOME steam steam://install/41070

cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Serious\ Sam\ Fusion\ 2017

echo "RunAsync(function()
  prj_bStartupAutoDetection = 0;
  men_bShowIntroCutscene = 0;
  men_bUseMenuSimulation = 0;

  -- wait until start screen is shown
  Wait(CustomEvent(\"StartScreen_Shown\"));
  -- handle initial interactions
  gamHandleInitialInteractions();
  
  -- use medium speed by default
  men_bShowIntroCutscene = 0;
  men_bUseMenuSimulation = 0;
  prj_psGameOptionPlayerSpeed = 1;
  -- make sure developer cheats are enabled
  cht_bEnableCheats = 2;
  -- enable the bot
  cht_bAutoTestBot = 1;
  -- optional: make bot skip terminals and QR codes
  bot_bSkipTerminalsAndMessages = 1;
  -- start a new game
  gam_strLevel = \"01_CairoSquare\"
  bmk_bAutoQuit = 1;
  bmkStartBenchmarking(3, 60);
  gamStart();
  Wait(Delay(36))
  bmkResults();
  Quit();
  
  -- wait until game ends
  BreakableRunHandled(
    WaitForever,
    -- if fame ended
    On(CustomEvent(\"GameEnded\")),
    function()
      print(\"Finished auto test bot run\");
      BreakRunHandled();
    end,
    -- if test bot failed
    On(CustomEvent(\"AutoTestBot_Failed\")),
    function()
      BreakRunHandled();
    end
  )
  -- quit the game after finished running the auto test bot
  quit();
end);" > pts-ss3-fusion-run.lua

cd ~

echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Serious\ Sam\ Fusion\ 2017/Log/Sam2017.log
cd \$DEBUG_REAL_HOME/.steam/steam/userdata/
STEAM_ID=\`ls\`
cd ~
cp -f Sam2017-\$3.ini \$DEBUG_REAL_HOME/.steam/steam/userdata/\$STEAM_ID/564310/local/SeriousSam2017.ini
sed -ie \"s/3840/\$1/g\" \$DEBUG_REAL_HOME/.steam/steam/userdata/\$STEAM_ID/564310/local/SeriousSam2017.ini
sed -ie \"s/2160/\$2/g\" \$DEBUG_REAL_HOME/.steam/steam/userdata/\$STEAM_ID/564310/local/SeriousSam2017.ini
sed -ie \"s/OpenGL/\$4/g\" \$DEBUG_REAL_HOME/.steam/steam/userdata/\$STEAM_ID/564310/local/SeriousSam2017.ini

export HOME=\$DEBUG_REAL_HOME
rm -f \$HOME/.steam/steam/steamapps/common/Serious\ Sam\ Fusion\ 2017/Temp/run.txt
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Serious\ Sam\ Fusion\ 2017/Log/Sam2017.log
sleep 5
steam -applaunch 564310 +exec pts-ss3-fusion-run.lua
sleep 80
while pgrep Sam2017 > /dev/null; do sleep 1; done;
sleep 10
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Serious\ Sam\ Fusion\ 2017/Log/Sam2017.log > \$LOG_FILE
" > ss3bfe
chmod +x ss3bfe
