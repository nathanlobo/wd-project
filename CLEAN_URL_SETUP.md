# How to Enable Clean URLs in XAMPP

## Step 1: Enable mod_rewrite in Apache

1. Open `C:\xampp\apache\conf\httpd.conf` in a text editor
2. Find this line (around line 169-171):
   ```
   #LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Remove the `#` to uncomment it:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

## Step 2: Allow .htaccess Override

1. In the same `httpd.conf` file, find this section (around line 230):
   ```
   <Directory "C:/xampp/htdocs">
       AllowOverride None
       Require all granted
   </Directory>
   ```
2. Change `AllowOverride None` to `AllowOverride All`:
   ```
   <Directory "C:/xampp/htdocs">
       AllowOverride All
       Require all granted
   </Directory>
   ```

## Step 3: Restart Apache

1. Open XAMPP Control Panel
2. Stop Apache
3. Start Apache again

## Step 4: Test Clean URLs

Now you can access your pages without `.php`:
- ✅ `http://localhost/Nathan/wd-project/messages` (instead of messages.php)
- ✅ `http://localhost/Nathan/wd-project/profile` (instead of profile.php)
- ✅ `http://localhost/Nathan/wd-project/notifications` (instead of notifications.php)

## Troubleshooting

If you get a 404 error:
- Make sure Apache was restarted
- Check that `.htaccess` file exists in your project root
- Verify `mod_rewrite` is enabled and `AllowOverride All` is set
