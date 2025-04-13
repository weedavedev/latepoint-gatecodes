#!/bin/bash

update_version_in_file() {
    local file_path="$1"
    local curr_version="$2"
    local new_version="$3"
    local file_type="$4"  # "Source" or "Zip" description
    
    # Update version numbers
    sed -i "s/Version: $curr_version/Version: $new_version/" "$file_path"
    sed -i "s/const VERSION = '$curr_version';/const VERSION = '$new_version';/" "$file_path"
    
    # Verify update was successful
    if grep -q "Version: $new_version" "$file_path" || grep -q "const VERSION = '$new_version'" "$file_path"; then
        echo "$file_type version updated to $new_version in $(basename "$file_path")"
    else
        echo "$file_type version number not updated... Manual update required to match."
    fi
}

#production ready script for wordpress plug ins.
#script will create a zip and output necessary feedback about plugin files. 
#TODO: 
#- create individuel functions for portablility 
#- add flow control with functions, current status and errors etc. implement optional operations choose 1-9.
#- add method to import parameters of files 
#- add more cross over checks for README/Mainfile/CSS VERISON numbers
#- Git commit before changing file version numbers
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
    PHP_SYNTAX=$(php -l "$MAIN_FILE" 2>&1)
    if [[ $PHP_SYNTAX == *"No syntax errors detected"* ]]; then
        echo -e "${GREEN}PHP syntax is valid${NC}"
    else 
        echo -e "${RED}ERROR IN PHP SCRIPT : $PHP_SYNTAX ${NC}"
    fi
else
    echo -e "${RED}PHP not found in PATH, skipping sytnax checks${NC}"
fi
        
# 2. Update Version Number 
# First get verison number in main file
CURR_VERSION=$(grep -oP "Version: \K[0-9]+\.[0-9]+\.[0-9]+" "$MAIN_FILE")
if [ -z "$CURR_VERSION" ]; then
    CURR_VERSION=$(grep -oP "const VERSION = '\K[0-9]+\.[0-9]+\.[0-9]+'" "$MAIN_FILE" | tr -d "'")
fi
if [ -z "$CURR_VERSION" ]; then
    echo -e "${RED}Could not find current version number"
fi

read -e -p "($CURR_VERSION) found, enter new number? " -i "$CURR_VERSION" RELEASE_VERSION
# 
#2.1 git commit to save changes

if command -v git /dev/null && [ -d ".git" ]; then
    read -p "Commit changes to repo? (Y/G/n) [G to exit and commit maually] " GIT_COMMIT
    if [[ "GIT_COMMIT" == "G" ]]; then
        exit 1 #exit to allow more control over git
    fi

    #show current git status before comfirming basic commit
    git status
    read -p "Confirm 'git commit -am Bumper commit version to $RELEASE_VERSION' command (Y/n)" GIT_COMMIT
    if [[ "$GIT_COMMIT" == "Y" ]]; then
        git add . 
        git commit -m "Bumper commit version to $RELEASE_VERSION"
        echo -e "${GREEN}Changes committed to Git${NC}"
    fi
fi
        
#
#then update the file in locations
if [ -z "$RELEASE_VERSION" ]; then
    RELEASE_VERSION=$CURR_VERSION
    echo -e "${BLUE}Using current version (${RELEASE_VERSION})${NC}"
else
    echo "Updating version number to $RELEASE_VERSION"
    
    read -p "Update both zip and source? (Y/(S)ource/(Z)ip/N)" UPDATE_LIST
    UPDATE_LIST="${UPDATE_LIST^^}"  # Convert to uppercase
    CURRENT_DIR="$(pwd)"    #set current directory

    # Update source file if requested
    if [[ "$UPDATE_LIST" == "Y" || "$UPDATE_LIST" == "S" ]]; then
        update_version_in_file "${CURRENT_DIR}/${MAIN_FILE}" "$CURR_VERSION" "$RELEASE_VERSION" "Source"
    fi

    # Update zip file if requested
    if [[ "$UPDATE_LIST" == "Y" || "$UPDATE_LIST" == "Z" ]]; then
        update_version_in_file "${TMP_PLUGIN_DIR}/${MAIN_FILE}" "$CURR_VERSION" "$RELEASE_VERSION" "Zip"
    fi
fi
#add README_VER checks as well

# 2. Set DEBUG = false 
echo "-- Setting debug mode to false"
sed -i 's/const DEBUG = true;/const DEBUG = false;/' "$TMP_PLUGIN_DIR/$MAIN_FILE" 
if grep -q "const DEBUG = true" "$TMP_PLUGIN_DIR/$MAIN_FILE"; then
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
