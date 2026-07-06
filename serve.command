#!/bin/bash
# Pocket Agency — local preview server
# Double-click this file, then open http://localhost:8000 in your browser.
cd "$(dirname "$0")"
echo "Serving Pocket Agency at http://localhost:8000  (Ctrl+C to stop)"
open "http://localhost:8000" 2>/dev/null &
python3 -m http.server 8000
