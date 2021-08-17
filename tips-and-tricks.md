chgrp www-data storage -R
chmod g+rwx storage -R
chown www-data:www-data storage -R

#ignore files
git rm --cached file

#use sass compiler npx
##install
- use terminal, and go to themes/fundatie dir
- npm install
- npx mix watch - localhost:3000 will reaload each time a file is changed