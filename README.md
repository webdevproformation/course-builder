# Installation


```sh
git init

git submodule add https://github.com/webdevproformation/course-builder build

cd build

composer dump-autoload
```

# Utilisation

```sh
cd ..
# All md files
php build/build.php -A

# uniquement les fichiers modifiés depuis 15 min
php build/build.php
```