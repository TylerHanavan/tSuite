# First, ensure you have Composer installed
sudo apt update
sudo apt install php-dom
sudo apt-get install php-xml
sudo apt-get install php-mbstring
sudo apt-get install php-zip unzip

# Need to install composer in /usr/local/bin/composer
# chmod +x /usr/local/bin/composer

# SELENIUM
#
# Need to install chromedriver and place it somewhere
# Need to install selenium-server and place it somewhere
#
# Need to install google-chrome-stable to test with Chrome
# wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
# sudo apt install ./google-chrome-stable_current_amd64.deb -y
#
# Launch Selenium with chromedriver
# java -Dwebdriver.chrome.driver=/home/tyler/local_tsuite_code/chromedriver -jar /home/tyler/local_tsuite_code/selenium-server-4.29.0.jar standalone