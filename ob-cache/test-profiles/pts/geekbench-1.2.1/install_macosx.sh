#!/bin/sh

unzip -o Geekbench-5.3.0-Mac.zip

echo "This test profile requires a GeekBench PRO license for command-line automation. Before running this test you must run:

          cd $HOME/
          ./Geekbench\ 5.app/Contents/Resources/geekbench5 -r <YOUR EMAIL> <YOUR LICENSE KEY>" > ~/install-message

echo "#!/bin/bash
././Geekbench\ 5.app/Contents/Resources/geekbench5 \$@ > \$LOG_FILE" > geekbench
chmod +x geekbench
