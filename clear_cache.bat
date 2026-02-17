@echo off
echo Limpiando cache de Laravel...
call npm run build
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan livewire:publish --assets
php artisan filament:assets
php artisan config:cache
php artisan view:cache
echo Limpieza completada.
pause