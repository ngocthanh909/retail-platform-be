cd ..
echo "checking disk public exist"
if [ -h public/storage ]; then
  echo "disk public exist. do nothing"
else
  echo "disk public not exist. setting up pubic disk"
  php artisan storage:link
fi
