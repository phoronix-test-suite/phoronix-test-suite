#!/bin/sh
rm -rf gtk
/cygdrive/c/Program\ Files/Git/cmd/git clone https://github.com/michaellarabel/gtk
/cygdrive/c/Program\ Files/Git/cmd/git --version > ~/install-footnote

echo "#!/bin/sh
cd git-target
/cygdrive/c/Program\ Files/Git/cmd/git log --since=2002-01-01 --until=2018-01-01 --oneline > 1.txt
/cygdrive/c/Program\ Files/Git/cmd/git diff --shortstat 2.24.0 3.93.0
/cygdrive/c/Program\ Files/Git/cmd/git checkout -b new_branch
/cygdrive/c/Program\ Files/Git/cmd/git config user.name \"Phoronix Test Suite\"
/cygdrive/c/Program\ Files/Git/cmd/git config --global user.email nowhere@example.com
mv gsk gsk2
/cygdrive/c/Program\ Files/Git/cmd/git add .
/cygdrive/c/Program\ Files/Git/cmd/git commit -a -m \"Changed directory\"
/cygdrive/c/Program\ Files/Git/cmd/git checkout master
/cygdrive/c/Program\ Files/Git/cmd/git merge new_branch
/cygdrive/c/Program\ Files/Git/cmd/git checkout 3.22.11
/cygdrive/c/Program\ Files/Git/cmd/git blame gtk/gtkaccelmap.c
/cygdrive/c/Program\ Files/Git/cmd/git diff --stat origin/master
/cygdrive/c/Program\ Files/Git/cmd/git log --grep \"x11\" --author \"Matthias Clasen\" --shortstat
/cygdrive/c/Program\ Files/Git/cmd/git log --author \"Matt\" --shortstat
/cygdrive/c/Program\ Files/Git/cmd/git --no-pager grep --color=never --extended-regexp --threads=\$NUM_CPU_CORES '(static|extern) (int|double) \*'
/cygdrive/c/Program\ Files/Git/cmd/git --version > ~/install-footnote" > git
chmod +x git


