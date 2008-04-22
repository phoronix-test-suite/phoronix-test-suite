#!/bin/sh

# Mandriva package installation

su root -c "urpmi --auto $@"
exit
