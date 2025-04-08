#!/bin/bash
#production ready script for wordpress plug ins.
#script will create a zip and output necessary feedback about plugin files. 
#TODO: 
#- create individuel functions for portablility 
#- add method to import parameters of files 
#- add more cross over checks for README/Mainfile/CSS VERISON numbers
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

# Check if files exist 
if [ ! -f "$MAIN_FILE" ]; then
    echo -e "${RED}ERROR: Main plugin file not found ${NC}"
    exit 1
fi

# Create temp directory to store files before zippin'
TMP_DIR=$(mktemp -d)
TMP_PLUGIN_DIR="$TMP_DIR/$PLUGIN_SLUG"
mkdir -p "$TMP_PLUGIN_DIR"

# Copy file to temp directory ready for conversion and mutilation.
echo "-- Copying files to tmp directory"
for file in "${FILES[@]}"; do
    # create sub directories if required 
    dir=$(dirname "$file")
    if [ "$dir" != "." ]; then 
        mkdir -p "$TMP_PLUGIN_DIR/$dir"
    fi
    cp "${file}" "$TMP_PLUGIN_DIR/$file"
    echo -e "${BLUE}Copied ${file} to $TMP_PLUGIN_DIR/$file...${NC}"
done
echo -e "${GREEN}Finished copying files${NC}"

# START OF TASKS TO DO IN TMP directory
################################################
# 1. Check php source code
echo "-- Checking PHP sytax..."
if command -v php > /dev/null; then
    PHPSYNTAX=$(php -l "$MAIN_FILE" 2>&1)
    if [[ $PHP_SYNTAX == *"No syntax errors detected"* ]]; then
        echo -e "${GREEN}PHP syntax is valid${NC}"
    else 
        echo -e "${RED}ERROR IN PHP SCRIPT : $PHP_SYNTAX ${NC}"
    fi
else
    echo -e "${RED}PHP not found in PATH, skipping sytnax checks${NC}"
fi
        
# 2. Update Version Number 
CURR_VERSION=$(grep -oP "Version: \K[0-9]+\.[0-9]+\.[0-9]+" "$MAIN_FILE")
if [ -z "$CURR_VERSION" ]; then
    CURR_VERSION=$(grep -oP "const VERSION = '\K[0-9]+\.[0-9]+\.[0-9]+'" "$MAIN_FILE" | tr -d "'")
fi
if [ -z "$CURR_VERSION" ]; then
    echo -e "${RED}Could not find current version number"
fi

echo "Update version number? current version(${CURR_VERSION})"
read -p "Enter new version number, or blank to not change: " RELEASE_VERSION

if [ -z "$RELEASE_VERSION" ]; then
    RELEASE_VERSION=$CURR_VERSION
    echo -e "${BLUE}Using current version (${RELEASE_VERSION})${NC}"
else
    echo -e "${GREEN}Version number updated to (${RELEASE_VERSION})${NC}"
fi

#add README_VER checks as well
#NEW_VER
RELEASE_VERSION="1.0.0" #update soon

# 2. Set DEBUG = false 
echo "-- Setting debug mode to false"
sed -i 's/const DEBUG = true;/const DEBUG = false;/' "$TMP_PLUGIN_DIR/$MAIN_FILE" 
if [ "$(grep -c "DEBUG = true" "$TMP_PLUGIN_DIR/$MAIN_FILE")" -ne 0 ]; then
    echo -e "${RED}Failed to set debug = false${NC}"
else
    echo -e "${GREEN}DEBUG = FALSE${NC}"
fi

#space for more functions...

#10. Create the ZIP file
ZIP_NAME="${PLUGIN_SLUG}-${RELEASE_VERSION}.zip"
echo "-- Starting to zip files into ${ZIP_NAME}"

# Create the ZIP file from the temp directory
#echo -e to change working text to blue, sucess message will reset.
(echo -e "${BLUE}" && cd "$TMP_DIR" && zip -r "$OLDPWD/$ZIP_NAME" "$PLUGIN_SLUG")

# Check if zip was created successfully
if [ -f "$ZIP_NAME" ]; then
    # List the contents of the zip to verify
    echo -e "${GREEN}ZIP created successfully. Contents:${NC}"
    unzip -l "$ZIP_NAME"
else
    echo -e "${RED}Failed to create ZIP file${NC}"
fi

#11. Remove temp directory
rm -rf "$TMP_DIR"
if [ ! -d "$TMP_DIR" ]; then
    echo -e "${GREEN}Removed TMP directory${NC}"
else
    echo -e "${RED}TMP might still exist...${NC}"
fi

#12. exit
echo "-- Thanks for flying with us today..."
