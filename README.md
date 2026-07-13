# création du moteur




```sh
git init

git submodule https://github.com/webdevproformation/course-builder build

cd build

composer dump-autoload

cd ..

php build/build.php
```