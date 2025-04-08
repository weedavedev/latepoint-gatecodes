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
echo "Copying files to tmp directory"
for file in "${FILES[@]}"; do
    # create sub directories if required 
    dir=$(dirname "$file")
    if [ "$dir" != "." ]; then 
        mkdir -p "$TMP_PLUGIN_DIR/$dir"
    fi
    cp "${file}" "$TMP_PLUGIN_DIR/$file"
    echo -e "${BLUE}Copied ${file} to $TMP_PLUGIN_DIR/$file...${NC}"
done
echo "Finished copying files"

# START OF TASKS TO DO IN TMP directory
################################################
# 1. Check php source code
# 2. Update Version Number 
RELEASE_VERSION="1.0.0" #update soon

# 2. Set DEBUG = false 
echo -e "${BLUE}Setting debug mode to false${NC}"
sed -i 's/const DEBUG = true;/const DEBUG = false;/' "$TMP_PLUGIN_DIR/$MAIN_FILE" 
if [ "$(grep -c "DEBUG = true" "$TMP_PLUGIN_DIR/$MAIN_FILE")" -ne 0 ]; then
    echo -e "${RED}Failed to set debug = false${NC}"
fi

#space for more functions...

#10. Create the ZIP file
ZIP_NAME="${PLUGIN_SLUG}-${RELEASE_VERSION}.zip"
echo "Starting to zip files into ${ZIP_NAME}"

# Create the ZIP file from the temp directory
(cd "$TMP_DIR" && zip -r "$OLDPWD/$ZIP_NAME" "$PLUGIN_SLUG")

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
echo -e "${GREEN}Removed TMP directory${NC}"

#12. exit
echo "Thanks for flying with us today..."
