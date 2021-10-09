#!/bin/bash -e

# Restore old resolution
#
xrandr --size "${VIDEO_WIDTH}x${VIDEO_HEIGHT}"
