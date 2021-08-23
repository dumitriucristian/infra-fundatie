chgrp www-data storage themes -R
chmod g+rwx storage themes -R
chown www-data:www-data themes storage -R

#ignore files
git rm --cached file

#use sass compiler npx
##install
- use terminal, and go to themes/fundatie dir
- npm install
- npx mix watch - localhost:3000 will reaload each time a file is changed


#https://octobercms.com/forum/post/how-to-add-more-fonts-to-froala-richeditor
