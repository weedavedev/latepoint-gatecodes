#!/bin/bash
update_version_in_file() {
    local file="$1"
    local curr_version="$2"
    local new_version="$3"
    local file_type="$4"  # "Source" or "Zip" description
    
    # Update version numbers
    sed -i "s/Version: $curr_version/Version: $new_version/" "./$file"
    sed -i "s/const VERSION = '$curr_version';/const VERSION = '$new_version';/" "./$file"
    
    # Verify update was successful
    if grep -q "Version: $new_version" "./$file" || grep -q "const VERSION = '$new_version'" "$file"; then
        echo "$file_type version updated to $new_version"
    else
        echo "$file_type version number not updated... Manual update required to match."
    fi
}
#TODO change flow so if its not updated nothing happens, save risk of files being changed. 
#
MAIN_FILE="file.php"
TMP_DIR="tmp/"
#extract version number 
CURR_VERSION=$(grep -oP "Version: \K[0-9]+\.[0-9]+\.[0-9]+" "./$MAIN_FILE")
if [ -z "$CURR_VERSION" ]; then
    #echo "'Version:' failed. Trying 'const VERSION = '"
    CURR_VERSION=$(grep -oP "const VERSION = '\K[0-9]+\.[0-9]+\.[0-9]+'" "./$MAIN_FILE" | tr -d "'")
fi

#if extraction was sucesfull...
if [ -z "$CURR_VERSION" ]; then
    echo "Error extracting VERSION NUMBER, we wont be able to update it if it doesnt exist."
else
    #update to a new version number?
    read -e -p "($CURR_VERSION) found, enter new number? " -i "$CURR_VERSION" RELEASE_VERSION
    if [ -z "$RELEASE_VERSION" ]; then
        RELEASE_VERSION="$CURR_VERSION"
    fi
    echo "Updating version number to $RELEASE_VERSION"
        
    read -p "Update both zip and source? (Y/(S)ource/(Z)ip/N)" UPDATE_LIST
    UPDATE_LIST="${UPDATE_LIST^^}"  # Convert to uppercase

    # Update source file if requested
    if [[ "$UPDATE_LIST" == "Y" || "$UPDATE_LIST" == "S" ]]; then
        update_version_in_file "$MAIN_FILE" "$CURR_VERSION" "$RELEASE_VERSION" "Source"
    fi

    # Update zip file if requested
    if [[ "$UPDATE_LIST" == "Y" || "$UPDATE_LIST" == "Z" ]]; then
        update_version_in_file "$TMP_DIR/$MAIN_FILE" "$CURR_VERSION" "$RELEASE_VERSION" "Zip"
    fi
fi
