#!/bin/sh
rm -rf gtk
git clone https://github.com/michaellarabel/gtk
git --version > ~/install-footnote

echo "#!/bin/sh
cd git-target
git log --since=2002-01-01 --until=2018-01-01 --oneline > 1.txt
git diff --shortstat 2.24.0 3.93.0
git checkout -b new_branch
git config user.name \"Phoronix Test Suite\"
git config --global user.email nowhere@example.com
mv gsk gsk2
git add .
git commit -a -m \"Changed directory\"
git checkout master
git merge new_branch
git checkout 3.22.11
git blame gtk/gtkaccelmap.c
git diff --stat origin/master
git log --grep \"x11\" --author \"Matthias Clasen\" --shortstat
git --version > ~/install-footnote" > git
chmod +x git


