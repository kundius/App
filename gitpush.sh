#!/bin/bash

read -p "Введите Сообщение коммита: " MESSAGE

BASE=$(dirname $(dirname $PWD))
APP_CORE=$BASE'/core/components/app'
APP_ASSETS=$BASE'/assets/components/app'
BUILD_CORE=$PWD'/core/components/app'
BUILD_ASSETS=$PWD'/assets/components/app'
CORE_SYMLINK=false
ASSETS_SYMLINK=false

if [ -L $BUILD_ASSETS ]
then
    echo 'Unlink assets'
    unlink $BUILD_ASSETS

    echo 'Copying assets'
    cp -rf $APP_ASSETS $BUILD_ASSETS

    ASSETS_SYMLINK=true
else
    echo 'Assets not symlink'
fi

if [ -L $BUILD_CORE ]
then
    echo 'Unlink core'
    unlink $BUILD_CORE

    echo 'Copying core'
    cp -rf $APP_CORE $BUILD_CORE

    CORE_SYMLINK=true
else
    echo 'Core not symlink'
fi

echo 'git add'
git add ./
echo 'git commit'
git commit -m $MESSAGE
echo 'git push'
git push

if [ $CORE_SYMLINK = true ]
then
    echo 'Creating core symlink'
    rm -rf $BUILD_ASSETS
    ln -s $APP_ASSETS $BUILD_ASSETS
fi

if [ $ASSETS_SYMLINK = true ]
then
    echo 'Creating assets symlink'
    rm -rf $BUILD_CORE
    ln -s $APP_CORE $BUILD_CORE
fi