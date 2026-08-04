#!/bin/bash
# reels agency — local preview server
# Double-click this file, then open http://localhost:8000 in your browser.
cd "$(dirname "$0")"
open "http://localhost:8000" 2>/dev/null &
python3 serve.py 8000
