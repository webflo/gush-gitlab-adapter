VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
    config.vm.box = "jubianchi/debian-wheezy-chef-amd64"

    config.vm.network "private_network", ip: "192.168.22.4"
    config.vm.network "forwarded_port", guest: 80, host: 2280

    config.vm.provision "shell", inline: <<SCRIPT
cd /tmp
wget https://downloads-packages.s3.amazonaws.com/debian-7.4/gitlab_6.9.0-omnibus.2-1_amd64.deb
sudo dpkg -i gitlab_6.9.0-omnibus.2-1_amd64.deb
sudo gitlab-ctl reconfigure
SCRIPT
end
