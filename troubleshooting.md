
#app key invalid or do not exist
add to .env file APP_KEY=null
run php artisan key:generate
key was generated remove "=null" from generated hash

#file_put_contents(c:\composer): failed to open stream: Permission denied  
clear cache     
1. ```php artisan cache:clear```    
change file rights      
2. ```chmod -R 777 storage vendor```    
refresh composer        
3. ```composer dump-autoload``` 

#untracked file present, unable to change branch    
delete untracked file and folders - BEWARE NOT RECOVERABLE    
```git clean -f -d```

#remove all changes - BEWARE NOT RECOVERABLE
```git fetch --all```
```git reset --hard origin/<branch name>```

#The only supported ciphers are AES-128-CBC and AES-256-CBC 

``` 
    php artisan key:generate     
    php artisan config:clear
    php artisan config:cache
```
#some random error 
-   check for .env file to be present
-   run  ``` composer update ```
