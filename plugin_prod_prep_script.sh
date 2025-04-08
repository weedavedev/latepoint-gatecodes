#!/bin/bash
#production ready script for wordpress plug ins.
#script will create a zip and output necessary feedback about plugin files. 


#TODO: 
#- create individuel functions for portablility 
#- add method to import parameters of files 
#
# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PLUGIN_SLUG="latepoint-gate-codes"
MAIN_FILE="latepoint-gate-codes.php"
README_FILE="README.md"
FILES=("latepoint-gate-codes.php" "assets/css/latepoint-gate-codes.css")

#check if files exist 
if [ ! -f "$MAIN_FILE" ]; then
    echo -e "${RED}ERROR: Main plugin file not found ${NC}"
    exit 1
fi

#create temp directory to store files before zippin'
TMP_DIR=$(mktemp -d)
PLUGIN_DIR="$TMP_DIR/$PLUGIN_SLUG"
mkdir -p "$PLUGIN_DIR"

#copy file to temp directory ready for conversion and mutilation.
echo "Copying file to tmp directory"

for file in "${FILES[@]}"; do
    # create sub directories if required 
    dir=$(dirname "$file")
    if [ "$dir" != "." ]; then 
        mkdir -p "$PLUGIN_DIR/$dir"
    fi

    cp "${file}" "$PLUGIN_DIR/$file"
    echo -e "${BLUE}Copied ${file} to $PLUGIN_DIR/$file...${NC}"
done

#START OF POTENTIAL TASKS TO DO IN TMP directory
################################################

#1. Set DEBUG = false 
echo -e "${BLUE}Debug mode set to false${NC}"
sed -i 's/const DEBUG = true;/const DEBUG = false;/' "$PLUGIN_DIR/$MAIN_FILE" 

#10. zip all tmp files into zip directory
#swap to tmp direcotyr and zip stuff up then moving it into the .zip
(cd "$TMP_DIR" && zip -r "../$PLUGIN_SLUG.zip" "$PLUGIN_SLUG")
# Check if zip was created successfully
if [ -f "$PLUGIN_SLUG.zip" ]; then
    # List the contents of the zip to verify
    echo -e "${GREEN}ZIP created successfully. Contents:${NC}"
    unzip -l "$PLUGIN_SLUG.zip"
else
    echo -e "${RED}Failed to create ZIP file${NC}"
fi

#11. Remove extra files
#remove tmp dir to save memory
rm -rf "$TMP_DIR"
if [ -f "$PLUGIN_SLUG.zip" ]; then
    echo -e "${RED}Failed to delete ${PLUGIN_SLUG}${NC}"
else 
    echo -e "${GREEN}Removed TMP directory${NC}"
fi

#12. Exit
echo "Thanks for flying with us today..."
