#!/bin/sh

## Probably not necessary, since the benchmark has the Nix deps pinned,
## so the size used by the /nix/store won't grow.
##
## On the other hand, whenever the Nix pins are changed,
## that is worth running once to reclaim the space used by old versions.
#
# nix-collect-garbage
