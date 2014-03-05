#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PWD="$(pwd)"

underline=`tput smul`
nounderline=`tput rmul`
bold=`tput bold`
normal=`tput sgr0`

BASE="${DIR}/.."

declare -A dirs
declare -A branches
dirs[MAPBENDER STARTER]='application'
branches[MAPBENDER STARTER]='develop'
dirs[MAPBENDER]='application/mapbender'
branches[MAPBENDER]='develop'
dirs[FOM]='application/fom'
branches[FOM]='develop'
dirs[OWSPROXY]='application/owsproxy'
branches[OWSPROXY]='develop'


function list_branches {
    printf "\n${bold}${underline}$1${nounderline}${normal} \n"

    printf "\n${underline}Merged Branches${nounderline} \n"

    for k in $(git branch -a --merged remotes/origin/$2|grep -v "\->"|sed s/^..//);do git log -1 --pretty=format:"%Cgreen%ci|%Cred%cr|%Creset%an|" "$k" 2>/dev/null|awk -F '|' '{printf "%s %-25s %-25s ", $1, $2, $3}';echo $k;done|sort|more

    printf "\n${underline}Unmerged Branches${nounderline} \n"
    for k in $(git branch -a --no-merged remotes/origin/$2|grep -v "\->"|sed s/^..//);do git log -1 --pretty=format:"%Cgreen%ci|%Cred%cr|%Creset%an|" "$k"|awk -F '|' '{printf "%s %-25s %-25s ", $1, $2, $3}';echo $k;done|sort|more
}

cd "${BASE}/application"

for repo in "${!dirs[@]}";
do
    cd "${BASE}/${dirs[$repo]}"
    #git fetch -a
done

for repo in "${!dirs[@]}";
do
    cd "${BASE}/${dirs[$repo]}"
    list_branches "$repo" "${branches["${repo}"]}"
done

cd "$PWD"
