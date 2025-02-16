# First, ensure you have Composer installed
sudo apt update
sudo apt install composer

# Install PHPUnit globally
composer global require phpunit/phpunit

# Add Composer's global bin to your PATH (add this to ~/.bashrc)
echo 'export PATH="$PATH:$HOME/.config/composer/vendor/bin"' >> ~/.bashrc
source ~/.bashrc