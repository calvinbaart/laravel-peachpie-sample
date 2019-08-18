# Install .NET Core # https://www.microsoft.com/net/download/linux-package-manager/ubuntu14-04/sdk-current

# Register Microsoft key and feed
wget -q https://packages.microsoft.com/config/ubuntu/16.04/packages-microsoft-prod.deb -O packages-microsoft-prod.deb
sudo dpkg -i packages-microsoft-prod.deb

# Install .NET Core SDK
sudo apt-get install apt-transport-https
sudo apt-get update
sudo apt-get install dotnet-sdk-2.2 nuget -y

# Install Python Pip and icdiff (http://www.jefftk.com/icdiff)

sudo apt-get -y install python-pip
pip -V
sudo pip install --upgrade icdiff