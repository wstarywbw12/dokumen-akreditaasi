git checkout --ours cookies/user_1.cookie
git add cookies/user_1.cookie

git commit -m "Resolve cookie conflict"

git pull origin main
git fetch origin
git reset --hard origin/main

systemctl restart php8.2-fpm